Absolutely, Minh — let’s build a practical, end‑to‑end **fine-tuning pipeline for CodeLlama** (with QLoRA so it runs on a single GPU). I’ll give you copy‑pasteable code and explain *why* each step matters. I’ll use `pathlib` for paths as you prefer.

> 💡 **What you’ll get**
>
> - Fine-tune **CodeLlama‑7B‑Instruct** using **QLoRA** (4‑bit)
> - Train on **code instruction data** (e.g., CodeAlpaca)
> - Evaluate quickly and run inference
> - Optional: merge LoRA and serve via a minimal API

---

## 0) Prerequisites

**Hardware (recommended minimum)**

- **GPU:** 12–16 GB VRAM (7B QLoRA fits; 8 GB works with smaller batch + CPU offload)
- **RAM:** 16–32 GB
- **Disk:** ~30–60 GB (model + datasets + checkpoints)

**Accounts**

- Hugging Face account, accepted terms for CodeLlama.
  - Model example: `codellama/CodeLlama-7b-Instruct-hf`
    (Request access on the model page, then login via CLI below)

**Environment**

```bash
# (optional) Create a clean env
conda create -n codellama-ft python=3.10 -y
conda activate codellama-ft

# CUDA must match your driver setup. bitsandbytes uses system CUDA.
pip install -U pip

# Core stack
pip install -U "transformers>=4.41" datasets accelerate peft bitsandbytes trl \
  evaluate scipy sentencepiece safetensors einops

# For serving and utilities later
pip install -U fastapi uvicorn

# Login to Hugging Face (opens a browser prompt)
huggingface-cli login
```

---

## 1) Pick a base model

For instruction-following code tasks, use the Instruct variant:

```python
BASE_MODEL = "codellama/CodeLlama-7b-Instruct-hf"  # requires access on HF
# Alternatives:
# "codellama/CodeLlama-13b-Instruct-hf" (better, more VRAM)
# If you don’t have access to CodeLlama, consider: "bigcode/starcoder2-7b" or "deepseek-ai/deepseek-coder-6.7b-instruct"
```

---

## 2) Choose a dataset

Start with a compact instruction dataset that maps well to coding tasks:

- **`sahil2801/CodeAlpaca-20k`** — instruction → code pairs
- (Optional) Add **code-only data** (e.g., `bigcode/the-stack-smol`) for “style” adaptation

We’ll use CodeAlpaca-20k first, then show how to add more later.

---

## 3) Project structure

We’ll keep everything under a working directory and use `pathlib`:

```
codellama-ft/
  ├─ data/                      # (auto-downloaded by datasets)
  ├─ outputs/
  │    ├─ checkpoints/
  │    └─ merged/
  └─ train_codellama.py
```

---

## 4) Training script (QLoRA with TRL SFTTrainer)

Create `train_codellama.py`:

```python
# train_codellama.py
from __future__ import annotations
from pathlib import Path
from dataclasses import dataclass
import os
import math
import torch

from datasets import load_dataset
from transformers import (
    AutoTokenizer,
    AutoModelForCausalLM,
    BitsAndBytesConfig,
    TrainingArguments,
)
from peft import LoraConfig, get_peft_model, prepare_model_for_kbit_training
from trl import SFTTrainer, DataCollatorForCompletionOnlyLM

# -----------------------
# Config (edit here)
# -----------------------
BASE_MODEL = os.getenv("BASE_MODEL", "codellama/CodeLlama-7b-Instruct-hf")
DATASET = os.getenv("DATASET", "sahil2801/CodeAlpaca-20k")
OUTPUT_DIR = Path(os.getenv("OUTPUT_DIR", "outputs/checkpoints"))
MERGED_DIR = Path(os.getenv("MERGED_DIR", "outputs/merged"))
MAX_SEQ_LEN = int(os.getenv("MAX_SEQ_LEN", "2048"))  # 2k is a safe default for VRAM
BATCH_SIZE = int(os.getenv("BATCH_SIZE", "2"))       # per-device batch
GR_ACCUM = int(os.getenv("GR_ACCUM", "8"))
LR = float(os.getenv("LR", "2e-4"))
EPOCHS = float(os.getenv("EPOCHS", "2"))
LOG_STEPS = int(os.getenv("LOG_STEPS", "20"))
SAVE_STEPS = int(os.getenv("SAVE_STEPS", "500"))
EVAL_STEPS = int(os.getenv("EVAL_STEPS", "500"))

# LoRA config
lora_cfg = LoraConfig(
    r=16,
    lora_alpha=32,
    target_modules=[
        "q_proj", "k_proj", "v_proj", "o_proj",
        "gate_proj", "up_proj", "down_proj"
    ],
    lora_dropout=0.05,
    bias="none",
    task_type="CAUSAL_LM"
)

# 4-bit load (QLoRA)
bnb_cfg = BitsAndBytesConfig(
    load_in_4bit=True,
    bnb_4bit_quant_type="nf4",
    bnb_4bit_use_double_quant=True,
    bnb_4bit_compute_dtype=torch.bfloat16
)

def format_example_as_chat(ex):
    """
    Convert CodeAlpaca-style fields into a chat message list compatible
    with tokenizer.apply_chat_template.
    """
    instruction = ex.get("instruction", "").strip()
    inp = ex.get("input", "").strip()
    output = ex.get("output", "").strip()

    user_text = instruction if not inp else f"{instruction}\n\nInput:\n{inp}"
    return [
        {"role": "system", "content": "You are a helpful coding assistant."},
        {"role": "user", "content": user_text},
        {"role": "assistant", "content": output},
    ]

def main():
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    MERGED_DIR.mkdir(parents=True, exist_ok=True)

    print(f"Loading tokenizer: {BASE_MODEL}")
    tokenizer = AutoTokenizer.from_pretrained(
        BASE_MODEL,
        use_fast=True,
        padding_side="right",
        trust_remote_code=True,
    )
    # Some LLaMA/CodeLlama tokenizers may not define a PAD
    if tokenizer.pad_token is None:
        tokenizer.pad_token = tokenizer.eos_token

    print("Loading dataset…")
    dataset = load_dataset(DATASET)
    # Use train split; some datasets only have train
    train_ds = dataset["train"]
    # Optionally create a small eval set:
    if "test" in dataset:
        eval_ds = dataset["test"]
    else:
        # take last 500 examples for eval
        eval_ds = train_ds.select(range(max(1, min(500, len(train_ds)//50))))
        train_ds = train_ds.select(range(len(train_ds) - len(eval_ds)))

    # Map to chat format
    def to_chat(ex):
        return {"messages": format_example_as_chat(ex)}

    train_ds = train_ds.map(to_chat, remove_columns=train_ds.column_names)
    eval_ds  = eval_ds.map(to_chat,  remove_columns=eval_ds.column_names)

    print("Loading base model in 4-bit (QLoRA)…")
    model = AutoModelForCausalLM.from_pretrained(
        BASE_MODEL,
        quantization_config=bnb_cfg,
        device_map="auto",
        trust_remote_code=True,
    )
    model.gradient_checkpointing_enable()
    model = prepare_model_for_kbit_training(model)
    model = get_peft_model(model, lora_cfg)
    model.print_trainable_parameters()

    # Build a collator that *only* learns to predict the assistant part.
    # This keeps supervision focused on outputs.
    # We rely on the tokenizer's chat template to construct prompts.
    # Use an assistant token pattern inferred from the chat template:
    # For CodeLlama Instruct, the template usually wraps assistant turns;
    # TRL provides a helper to find the assistant part by special tokens.
    # If the template lacks special tokens, we fallback to label masking based on roles.

    # TRL can handle chat formatting internally if we pass 'formatting_func'.
    # Alternatively, we can pre-tokenize. We'll use SFTTrainer's formatting_func.
    def formatting_func(example):
        # example is a dict with "messages": [ {role, content}, ... ]
        # We convert it to a *single string* using the tokenizer's chat template.
        text = tokenizer.apply_chat_template(
            example["messages"],
            tokenize=False,
            add_generation_prompt=False,
        )
        return text

    # DataCollator to mask non-assistant tokens:
    # The tag is auto-detected for many chat templates. If this fails on your setup,
    # you can set 'assistant_role="assistant"'
    collator = DataCollatorForCompletionOnlyLM(
        instruction_template=None,  # autodetect via chat template
        response_template=None,
        tokenizer=tokenizer,
        mlm=False,
    )

    args = TrainingArguments(
        output_dir=str(OUTPUT_DIR),
        per_device_train_batch_size=BATCH_SIZE,
        per_device_eval_batch_size=max(1, BATCH_SIZE//2),
        gradient_accumulation_steps=GR_ACCUM,
        learning_rate=LR,
        lr_scheduler_type="cosine",
        warmup_ratio=0.05,
        num_train_epochs=EPOCHS,
        logging_steps=LOG_STEPS,
        save_steps=SAVE_STEPS,
        evaluation_strategy="steps",
        eval_steps=EVAL_STEPS,
        bf16=torch.cuda.is_available(),   # use bf16 if supported
        fp16=not torch.cuda.is_bf16_supported() if torch.cuda.is_available() else False,
        gradient_checkpointing=True,
        optim="paged_adamw_8bit",
        weight_decay=0.05,
        max_grad_norm=0.3,
        group_by_length=True,
        report_to="none",
    )

    trainer = SFTTrainer(
        model=model,
        tokenizer=tokenizer,
        train_dataset=train_ds,
        eval_dataset=eval_ds,
        formatting_func=formatting_func,
        data_collator=collator,
        packing=True,                # pack multiple samples up to MAX_SEQ_LEN
        max_seq_length=MAX_SEQ_LEN,
        args=args,
    )

    print("Starting training…")
    trainer.train()

    print("Saving adapter checkpoint…")
    trainer.model.save_pretrained(str(OUTPUT_DIR))
    tokenizer.save_pretrained(str(OUTPUT_DIR))

    # Optional: merge LoRA into base weights (uses more RAM/VRAM)
    try:
        print("Merging LoRA weights into base model (optional)…")
        merged = trainer.model.merge_and_unload()
        merged.save_pretrained(
            str(MERGED_DIR),
            safe_serialization=True
        )
        tokenizer.save_pretrained(str(MERGED_DIR))
        print(f"Merged model saved to: {MERGED_DIR}")
    except Exception as e:
        print(f"Merge step skipped or failed: {e}")
        print(f"You can still use the adapter at: {OUTPUT_DIR}")

if __name__ == "__main__":
    main()
```

### Run training

```bash
# Make sure you have access to the base model on HF.
# Accelerate will autodetect GPUs.
python train_codellama.py
```

> ⏱️ **Approx time**: On a single 24GB GPU, 2 epochs over CodeAlpaca‑20k with QLoRA is typically **1–3 hours**. On 12GB, reduce `BATCH_SIZE` (1) and increase `GR_ACCUM`.

---

## 5) Quick evaluation (perplexity & sample generations)

Create `quick_eval.py` to sanity-check:

```python
# quick_eval.py
from pathlib import Path
import torch
from transformers import AutoTokenizer, AutoModelForCausalLM

CKPT_DIR = Path("outputs/checkpoints")  # or outputs/merged

def chat(model, tokenizer, prompt, max_new_tokens=256, temperature=0.2, top_p=0.95):
    messages = [
        {"role": "system", "content": "You are a helpful coding assistant."},
        {"role": "user", "content": prompt},
    ]
    inputs = tokenizer.apply_chat_template(
        messages, add_generation_prompt=True, return_tensors="pt"
    ).to(model.device)

    with torch.no_grad():
        out = model.generate(
            inputs,
            max_new_tokens=max_new_tokens,
            temperature=temperature,
            top_p=top_p,
            do_sample=True,
            pad_token_id=tokenizer.eos_token_id,
        )
    text = tokenizer.decode(out[0], skip_special_tokens=True)
    return text

def main():
    tokenizer = AutoTokenizer.from_pretrained(CKPT_DIR, use_fast=True)
    model = AutoModelForCausalLM.from_pretrained(
        CKPT_DIR,
        torch_dtype=torch.bfloat16 if torch.cuda.is_available() else torch.float32,
        device_map="auto",
    )

    prompt = "Write a Python function to parse a JSON file and return a flattened dict (dot notation)."
    print(chat(model, tokenizer, prompt))

if __name__ == "__main__":
    main()
```

Run:

```bash
python quick_eval.py
```

---

## 6) Use the adapter without merging (memory friendly)

If you skipped merging, load **base model + adapter**:

```python
from transformers import AutoTokenizer, AutoModelForCausalLM, BitsAndBytesConfig
from peft import PeftModel
import torch

BASE_MODEL = "codellama/CodeLlama-7b-Instruct-hf"
ADAPTER_DIR = "outputs/checkpoints"

bnb_cfg = BitsAndBytesConfig(
    load_in_4bit=True, bnb_4bit_quant_type="nf4",
    bnb_4bit_use_double_quant=True, bnb_4bit_compute_dtype=torch.bfloat16
)

tokenizer = AutoTokenizer.from_pretrained(BASE_MODEL, use_fast=True)
if tokenizer.pad_token is None:
    tokenizer.pad_token = tokenizer.eos_token

base = AutoModelForCausalLM.from_pretrained(
    BASE_MODEL, quantization_config=bnb_cfg, device_map="auto"
)
model = PeftModel.from_pretrained(base, ADAPTER_DIR)
model.eval()
```

---

## 7) Minimal API for local use (OpenAI‑style not required)

Create `serve.py`:

```python
# serve.py
from pathlib import Path
from fastapi import FastAPI
from pydantic import BaseModel
import torch
from transformers import AutoTokenizer, AutoModelForCausalLM

CKPT_DIR = Path("outputs/merged")  # or outputs/checkpoints + adapter loading

app = FastAPI()
tokenizer = AutoTokenizer.from_pretrained(CKPT_DIR, use_fast=True)
model = AutoModelForCausalLM.from_pretrained(
    CKPT_DIR,
    torch_dtype=torch.bfloat16 if torch.cuda.is_available() else torch.float32,
    device_map="auto",
)
model.eval()

class GenerateReq(BaseModel):
    prompt: str
    max_new_tokens: int = 256
    temperature: float = 0.2
    top_p: float = 0.95

@app.post("/generate")
def generate(req: GenerateReq):
    messages = [
        {"role": "system", "content": "You are a helpful coding assistant."},
        {"role": "user", "content": req.prompt},
    ]
    inputs = tokenizer.apply_chat_template(
        messages, add_generation_prompt=True, return_tensors="pt"
    ).to(model.device)

    with torch.no_grad():
        out = model.generate(
            inputs,
            max_new_tokens=req.max_new_tokens,
            temperature=req.temperature,
            top_p=req.top_p,
            do_sample=True,
            pad_token_id=tokenizer.eos_token_id,
        )
    text = tokenizer.decode(out[0], skip_special_tokens=True)
    return {"text": text}

# Run: uvicorn serve:app --host 0.0.0.0 --port 8000
```

---

## 8) (Optional) Add more data sources

If you want to add pure code snippets (style/knowledge, no instructions):

```python
from datasets import load_dataset
stack = load_dataset("bigcode/the-stack-smol", "data", split="train")
# Filter to Python only if the dataset provides language metadata (varies by subset)
```

You’d then switch from `SFTTrainer` to a standard Causal LM objective or keep SFT but format samples as a single “user asks → assistant completes with code” template. Mixing 70–80% instruction data with 20–30% raw code often yields better behavior for a coding assistant.

---

## 9) Tips, VRAM, and troubleshooting

- **VRAM targets (approx, QLoRA)**
  - 7B: **10–12 GB** (batch=2, seq=2k)
  - 13B: **18–24 GB** (reduce batch/seq length as needed)
- **Sequence length**: Increasing `MAX_SEQ_LEN` helps with long code, but **VRAM grows linearly**.
- **Speed**: Use `bf16` if your GPU supports it (A100, 4090, etc.). Otherwise `fp16`.
- **Stability**: If OOM:
  - Reduce `BATCH_SIZE` to 1, increase `GR_ACCUM`
  - Lower `MAX_SEQ_LEN` to 1024–1536
  - Disable `packing` (more stable, slower)
- **Quality**:
  - Use **better data** (clean, de‑duplicated, license‑safe)
  - Add **unit tests** to outputs in your dataset (teaches TDD-like completions)
  - Add **tool-use examples** (e.g., docstring-first, explain-then-code)
- **Merging adapters**: Needed if you want a single artifact without PEFT at inference (easier to deploy on CPU-only or multiple frameworks).
- **Tokenizer PAD**: Many LLaMA-family tokenizers miss `pad_token` — set it to `eos_token`.
- **Determinism**: Set `torch.backends.cuda.matmul.allow_tf32 = True` (speed) or manage seeds via `TrainingArguments(seed=...)`.

---

## 10) Next steps (nice upgrades)

- **Evaluation**: Try `evalplus`/`HumanEval` pipelines when you’re ready.
- **IDE integration**: Point **Continue**, **Aider**, or custom VSCode extension to your `/generate` endpoint.
- **RAG for codebase**: Add a vector index (e.g., `chroma` + `ast`-aware chunking) to ground generations in your repo.
- **Guardrails**: Add system prompts and style constraints (e.g., prefer `pathlib`, add type hints, include tests).

---

## Quick sanity checklist

- [ ] Accepted model license and `huggingface-cli login` done
- [ ] Enough VRAM / smaller `BATCH_SIZE` + `GR_ACCUM` set
- [ ] Training runs without OOM
- [ ] `quick_eval.py` produces sensible code
- [ ] Decide whether to use **merged** weights or **adapter** for inference

---

## Want me to tailor this?

A few questions so I can tune the scripts for you:

1. **Your GPU & VRAM?** (e.g., 3060 12GB, 3090 24GB, A6000 48GB)
2. **Target languages?** (Python only or also JS/TS, Java, Go, etc.)
3. **Context length needs?** (Do you want 4k, 8k, or longer?)
4. **IDE integration?** (VS Code via Continue/Aider or your own LSP?)

Share these and I’ll adjust batch sizes, sequence length, and even provide a VS Code integration template.

Awesome, Minh — since you’re on a **MacBook Air (Apple Silicon, M‑series)** and want **VS Code** integration, the most practical path is:

- **Fine‑tune locally with Apple’s MLX stack (LoRA/QLoRA)** → it runs on the Mac GPU (Metal) and is designed for Apple Silicon. [1](https://developer.apple.com/videos/play/wwdc2025/298/)
- Avoid CUDA‑only tooling like `bitsandbytes` on macOS; it’s primarily built for NVIDIA GPUs and Apple/MPS support has historically been limited/experimental. [2](https://github.com/bitsandbytes-foundation/bitsandbytes/issues/252)[3](https://github.com/bitsandbytes-foundation/bitsandbytes/issues/485)
- Serve your fine‑tuned model via an **OpenAI‑compatible local server for MLX**, then point **Continue (VS Code extension)** at it. [4](https://pypi.org/project/mlx-openai-server/)[5](https://docs.continue.dev/guides/ollama-guide)

Below is a **step‑by‑step** you can copy/paste. I’ll tailor defaults for a base MacBook Air (typically **8–16 GB unified memory**). If **7B CodeLlama** feels tight, I’ll show a smaller backup (3B coder) that fine‑tunes comfortably on 8 GB.

---

## Overview of the plan

1) **Install** MLX fine‑tuning tools
2) **Get access** to CodeLlama (Meta license)
3) **Prepare a small instruction dataset** (Python-only for now)
4) **Run LoRA fine‑tuning on Mac (MLX)**
5) **Generate & sanity‑check** outputs locally
6) **Serve** the model with an **OpenAI‑compatible MLX server**
7) **Wire up VS Code** (Continue) to your local server
8) **(If 7B doesn’t fit)** swap to a smaller coder model; or fine‑tune in the cloud and run locally

---

## 1) Setup on macOS (Apple Silicon)

Create a clean environment and install **MLX LoRA trainer**:

```bash
# (optional) macOS system prep
xcode-select --install

# Python venv
python3 -m venv ~/.venvs/mlxft
source ~/.venvs/mlxft/bin/activate
python -m pip install -U pip

# Core: MLX + MLX-LM-LoRA trainer + datasets + HF CLI
pip install -U mlx datasets huggingface_hub mlx-lm-lora

# (for serving later)
pip install -U mlx-openai-server
```

- **Why MLX?** Apple’s MLX/MLX‑LM is optimized for Apple Silicon and supports **fine‑tuning** on‑device. [1](https://developer.apple.com/videos/play/wwdc2025/298/)
- **Why `mlx-lm-lora`?** It’s a CLI/SDK that fine‑tunes LLMs on MLX with **LoRA/DoRA**, supports **QLoRA (4‑, 6‑, 8‑bit)**, and multiple training modes including SFT/DPO/ORPO. [6](https://github.com/Goekdeniz-Guelmez/mlx-lm-lora)

---

## 2) Request access to CodeLlama (once)

CodeLlama is gated on Hugging Face; click *“Access repository”* (Meta’s Llama community license). [7](https://huggingface.co/meta-llama/CodeLlama-7b-Instruct-hf)

```bash
huggingface-cli login  # paste your HF token
```

> **Tip:** If MLX fails to pull `meta-llama/CodeLlama-7b-Instruct-hf` directly, use the **MLX‑converted variant** from `mlx-community` (see below). [8](https://huggingface.co/mlx-community/CodeLlama-7b-mlx)

---

## 3) Make a tiny Python‑focused dataset (CodeAlpaca → chat JSONL)

We’ll convert **CodeAlpaca‑20k** style examples into **chat `messages`** JSONL (the format MLX LoRA accepts), and keep it small at first (1–2k records) for fast iterations. The LoRA trainer natively supports `messages` datasets. [6](https://github.com/Goekdeniz-Guelmez/mlx-lm-lora)

Create `prep_data.py`:

```python
# prep_data.py
from pathlib import Path
from datasets import load_dataset
import json, random

OUT = Path("data/python-chat")
OUT.mkdir(parents=True, exist_ok=True)

# CodeAlpaca-20k: instruction, input, output
ds = load_dataset("sahil2801/CodeAlpaca-20k", split="train")

def to_chat(ex):
    instr = (ex.get("instruction") or "").strip()
    inp   = (ex.get("input") or "").strip()
    outp  = (ex.get("output") or "").strip()
    # Focus on Python tasks only (cheap heuristic)
    if "python" not in (instr.lower() + " " + inp.lower() + " " + outp.lower()):
        return None
    user = instr if not inp else f"{instr}\n\nInput:\n{inp}"
    return {
        "messages": [
            {"role": "system", "content": "You are a helpful coding assistant for Python."},
            {"role": "user", "content": user},
            {"role": "assistant", "content": outp}
        ]
    }

records = []
for ex in ds:
    r = to_chat(ex)
    if r:
        records.append(r)

random.shuffle(records)
records = records[:2000]  # keep it small for first run

n_test = max(100, len(records)//10)
test = records[:n_test]
valid = records[n_test:2*n_test]
train = records[2*n_test:]

def dump(fn, rows):
    with open(OUT / fn, "w", encoding="utf-8") as f:
        for r in rows:
            f.write(json.dumps(r, ensure_ascii=False) + "\n")

dump("train.jsonl", train)
dump("valid.jsonl", valid)
dump("test.jsonl", test)

print("Wrote:", OUT)
```

```bash
python prep_data.py
```

- MLX examples/tutorials also show converting datasets into **`train.jsonl` / `valid.jsonl` / `test.jsonl`** with `messages` (system/user/assistant) — same structure we use here. [9](https://heidloff.net/article/apple-mlx-fine-tuning/)[6](https://github.com/Goekdeniz-Guelmez/mlx-lm-lora)

---

## 4) Choose the base model (two working options)

**Option A — CodeLlama (7B Instruct):**

- Hugging Face ID: `meta-llama/CodeLlama-7b-Instruct-hf` (requires access) [7](https://huggingface.co/meta-llama/CodeLlama-7b-Instruct-hf)
- If MLX cannot convert on the fly, use MLX‑community weights: `mlx-community/CodeLlama-7b-mlx` (base). [8](https://huggingface.co/mlx-community/CodeLlama-7b-mlx)

**Option B — Smaller coder (safer on 8 GB):**
Try a **~3B coder** first (e.g., a Qwen 2.5 Coder 3B Instruct). MLX‑LoRA supports many bases beyond LLaMA/CodeLlama if you decide to switch to a lighter model. [6](https://github.com/Goekdeniz-Guelmez/mlx-lm-lora)

> We can start with CodeLlama‑7B and, if memory is tight, switch to a 3B coder with the **same pipeline**.

---

## 5) Fine‑tune with MLX‑LM‑LoRA (SFT)

The trainer’s **main command is `mlx_lm_lora.train`**. It supports SFT and preference training (DPO/ORPO/etc.), plus **LoRA / DoRA / full** and **quantized training** (QLoRA). We’ll do SFT + LoRA and keep batch small. You can see all flags via `--help`. [6](https://github.com/Goekdeniz-Guelmez/mlx-lm-lora)

### 5.1 Minimal LoRA run (first pass)

```bash
# Pick your model ID (uncomment one)
# MODEL="meta-llama/CodeLlama-7b-Instruct-hf"    # requires HF access
MODEL="mlx-community/CodeLlama-7b-mlx"           # MLX-converted base (7B)

DATA="data/python-chat"  # dir with train.jsonl/valid.jsonl/test.jsonl
OUT="outputs/codellama7b-python-lora"

# First pass: small steps to test the loop
mlx_lm_lora.train \
  --model "$MODEL" \
  --train \
  --train-mode sft \
  --train-type lora \
  --data "$DATA" \
  --batch-size 1 \
  --gradient-accumulation-steps 8 \
  --learning-rate 1e-4 \
  --iters 300 \
  --max-seq-length 1024 \
  --mask-prompt true \
  --output-dir "$OUT"
```

**Notes**

- `--train-type lora` enables parameter‑efficient tuning.
- `--mask-prompt` ensures we compute loss only on the assistant’s reply (not on system/user).
- Reduce `--max-seq-length` and keep `--batch-size 1` if you hit memory pressure; increase `iters` for more training signal. The trainer supports **QLoRA** with 4/6/8‑bit quantized base weights if you need to lower memory further — check the exact quantization flags for your installed version via `--help`. [6](https://github.com/Goekdeniz-Guelmez/mlx-lm-lora)

> If **7B still OOMs** on your Air, swap `MODEL` to a ~**3B coder** for a smooth experience, then come back to 7B later. [6](https://github.com/Goekdeniz-Guelmez/mlx-lm-lora)

---

## 6) Quick local generation (sanity check)

You can generate with **MLX‑LM** (simple CLI) using your base + LoRA adapter directory, or point to the merged weights if you merge (merging is optional in LoRA workflows).

**Simple generation with MLX‑LM (base weights):**

```bash
# Try with the base first to ensure MLX can run it
mlx_lm.generate --model "$MODEL" --prompt "Write a Python function that flattens nested dicts using dot notation."
```

MLX’s `generate` CLI is a quick way to validate your install and model loading locally on Apple Silicon. [9](https://heidloff.net/article/apple-mlx-fine-tuning/)

> To apply your LoRA at inference, the MLX‑LoRA project provides evaluation/generation utilities and documents how to use adapters; consult its README for the exact adapter loading flags in your version. [6](https://github.com/Goekdeniz-Guelmez/mlx-lm-lora)

---

## 7) Serve your (fine‑tuned) model locally (OpenAI‑compatible)

Use an **MLX OpenAI‑compatible server** so VS Code tooling can talk to it like it’s the OpenAI API.

```bash
# In your venv
pip install -U mlx-openai-server mlx-lm

# Example: serve a model by HF ID
mlx-openai-server serve --model "$MODEL" --host 127.0.0.1 --port 8000
```

The server exposes **`/v1/chat/completions`** endpoints compatible with OpenAI clients, making it a drop‑in for local development. [4](https://pypi.org/project/mlx-openai-server/)[10](https://github.com/cubist38/mlx-openai-server)

> If you want to serve **your LoRA‑tuned weights**, point the server to the **output directory** created by the trainer (or its merged model path) and follow the server’s README for specifying **adapters** if your run produced LoRA adapters instead of merged weights. [4](https://pypi.org/project/mlx-openai-server/)

---

## 8) VS Code integration (Continue)

Install **Continue** from the VS Code marketplace, then wire it to your local server. Continue supports local providers (Ollama, LM Studio, custom/OpenAI‑compatible). We’ll use the **OpenAI provider** pointing at your MLX server. [5](https://docs.continue.dev/guides/ollama-guide)

Open **Continue: Open Config** and add a model block similar to:

```yaml
# ~/.continue/config.yaml (or use Continue: Open Config to edit)
models:
  - name: "MLX CodeLlama (local)"
    provider: "openai"
    model: "mlx-codellama"          # arbitrary label shown in UI
    apiBase: "http://127.0.0.1:8000/v1"
    apiKey: "sk-local-dummy"        # placeholder; server ignores/accepts any
    roles: ["chat", "autocomplete", "edit", "apply"]
```

- Continue’s docs show how to connect local providers (e.g., Ollama) and custom endpoints—exactly this pattern. [5](https://docs.continue.dev/guides/ollama-guide)

> Alternative: If you later decide to host GGUF models with **Ollama**, Continue has a first‑class guide for that as well. [5](https://docs.continue.dev/guides/ollama-guide)

---

## 9) Troubleshooting & tips on a base MacBook Air

- **BNB/QLoRA on macOS:** The common QLoRA stack (`bitsandbytes` 4‑bit with CUDA) targets NVIDIA GPUs; macOS/MPS support isn’t turnkey, which is why we use MLX instead. [2](https://github.com/bitsandbytes-foundation/bitsandbytes/issues/252)[3](https://github.com/bitsandbytes-foundation/bitsandbytes/issues/485)
- **Memory pressure:**
  - Lower `--max-seq-length` (e.g., 768–1024)
  - Keep `--batch-size 1` and increase `--gradient-accumulation-steps`
  - Prefer smaller bases (≈3B) for faster iteration; MLX‑LoRA supports many architectures beyond CodeLlama. [6](https://github.com/Goekdeniz-Guelmez/mlx-lm-lora)
- **MLX‑community weights:** For CodeLlama, the **MLX‑converted** repository can simplify loading on Mac. [8](https://huggingface.co/mlx-community/CodeLlama-7b-mlx)
- **Learning resources:** Apple’s **WWDC session** on MLX shows fine‑tuning & quantization concepts on Apple Silicon — worth a skim. [1](https://developer.apple.com/videos/play/wwdc2025/298/)

---

## 10) Optional: Fine‑tune in the cloud, run locally on Mac

If you want to stick with the **Hugging Face / TRL** QLoRA pipeline I shared earlier (Linux + NVIDIA), do the training on a GPU VM or Colab, then **convert/serve on Mac** using MLX. Keep in mind, that CUDA‑based QLoRA relies on `bitsandbytes`. [11](https://huggingface.co/docs/bitsandbytes/main/en/installation)

---

## What you’ll have at the end

- A LoRA‑tuned CodeLlama (or smaller coder) checkpoint under `outputs/...`
- A local **OpenAI‑compatible** endpoint: `http://127.0.0.1:8000/v1`
- VS Code **Continue** pointing to your local model for chat, edits, and autocomplete.

---

## Quick next actions for you

1) **Tell me your exact RAM** (8 GB or 16 GB). If it’s **8 GB**, I’ll give you a ready‑made config for a **3B coder** first, then we can try 7B with QLoRA.
2) Do you want me to generate a **single script** (`make.sh`) that: creates the venv, installs deps, prepares data, runs training, then starts the server? I can also drop in a **Continue config snippet** that prefers `pathlib` in code suggestions (your style).

If you prefer, I’ll tailor the dataset to **Python tasks you do at work** (file I/O with `pathlib`, unit tests, docstrings) so the assistant matches your coding vibe.

Great — **16 GB RAM** on your MacBook Air is enough to fine‑tune a **7B** model with **LoRA** on Apple Silicon using **MLX**, as long as we keep batch size small and sequence length reasonable. Below I’ve tailored the steps and defaults for your machine, plus a one‑shot setup script and VS Code (Continue) wiring.

> **Why MLX on Mac?** Apple’s MLX stack is designed to run and **fine‑tune** LLMs efficiently on Apple Silicon GPUs (Metal). It’s the right tool here, whereas typical QLoRA stacks that rely on `bitsandbytes` are primarily CUDA/NVIDIA‑oriented and not straightforward on macOS. [1](https://developer.apple.com/videos/play/wwdc2025/298/)[2](https://heidloff.net/article/apple-mlx-fine-tuning/)[3](https://github.com/bitsandbytes-foundation/bitsandbytes/issues/252)[4](https://huggingface.co/docs/bitsandbytes/main/en/installation)

---

## Plan (tailored for 16 GB)

1) Install MLX + MLX‑LoRA trainer
2) Get CodeLlama access (Meta‑gated)
3) Prepare a small **Python‑only** instruction dataset
4) Fine‑tune **CodeLlama‑7B** with **LoRA** (SFT)
5) Sanity‑check generations locally
6) Serve your model with an **OpenAI‑compatible** local server
7) Connect **VS Code → Continue** to your local server
8) (Optional) If memory is tight, start with a ~3B coder and come back to 7B

---

## 0) One‑shot bootstrap (you can copy/paste)

Create `bootstrap_ft.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail

# --- Settings you can tweak ---
MODEL="mlx-community/CodeLlama-7b-mlx"     # MLX-converted CodeLlama 7B (base)  ✅
# If you already have access and prefer instruct weights via HF Transformers:
# MODEL="meta-llama/CodeLlama-7b-Instruct-hf"  # requires access grant on HF

DATA_DIR="data/python-chat"
OUT_DIR="outputs/codellama7b-python-lora"
SEQ_LEN=1024             # 1024–1536 recommended on 16 GB for stability
BATCH_SIZE=1
GRAD_ACCUM=16
LR=1e-4
ITERS=800                # increase later once everything works

# --- System prep ---
xcode-select --install || true

# --- Python venv ---
python3 -m venv ~/.venvs/mlxft
source ~/.venvs/mlxft/bin/activate
python -m pip install -U pip

# --- Install packages ---
pip install -U mlx datasets huggingface_hub mlx-lm-lora
pip install -U mlx-openai-server mlx-lm  # serving & quick gen

# --- Login to HF (press Enter if already logged in) ---
if ! huggingface-cli whoami &>/dev/null; then
  echo ">> Login to Hugging Face (paste token)"
  huggingface-cli login
fi

# --- Prepare a small Python-focused dataset ---
python << 'PY'
from pathlib import Path
from datasets import load_dataset
import json, random

OUT = Path("data/python-chat"); OUT.mkdir(parents=True, exist_ok=True)
ds = load_dataset("sahil2801/CodeAlpaca-20k", split="train")

def to_chat(ex):
    instr = (ex.get("instruction") or "").strip()
    inp   = (ex.get("input") or "").strip()
    outp  = (ex.get("output") or "").strip()
    # Keep Python-oriented samples
    if "python" not in (instr.lower() + " " + inp.lower() + " " + outp.lower()):
        return None
    user = instr if not inp else f"{instr}\n\nInput:\n{inp}"
    return {
        "messages": [
            {"role": "system", "content": "You are a helpful coding assistant for Python. Prefer pathlib and type hints."},
            {"role": "user", "content": user},
            {"role": "assistant", "content": outp}
        ]
    }

records = [r for ex in ds if (r:=to_chat(ex))]
random.shuffle(records)
records = records[:2000]  # small first run

n_test = max(100, len(records)//10)
test  = records[:n_test]
valid = records[n_test:2*n_test]
train = records[2*n_test:]

def dump(fn, rows):
    with open(OUT / fn, "w", encoding="utf-8") as f:
        for r in rows:
            f.write(json.dumps(r, ensure_ascii=False) + "\n")

dump("train.jsonl", train)
dump("valid.jsonl", valid)
dump("test.jsonl",  test)
print("Wrote dataset to", OUT)
PY

# --- Fine-tune with MLX LoRA (SFT) ---
mkdir -p "$OUT_DIR"
mlx_lm_lora.train \
  --model "$MODEL" \
  --train \
  --train-mode sft \
  --train-type lora \
  --data "$DATA_DIR" \
  --batch-size $BATCH_SIZE \
  --gradient-accumulation-steps $GRAD_ACCUM \
  --learning-rate $LR \
  --iters $ITERS \
  --max-seq-length $SEQ_LEN \
  --mask-prompt true \
  --output-dir "$OUT_DIR"

echo ">> Training finished. Output/adapter at: $OUT_DIR"

# --- Quick base-model generation sanity check ---
mlx_lm.generate --model "$MODEL" \
  --prompt "Write a Python function that flattens a nested dict to dot notation using pathlib if possible."

echo ">> To serve your model locally (OpenAI-compatible API):"
echo "mlx-openai-server serve --model \"$MODEL\" --host 127.0.0.1 --port 8000"
echo ">> If you trained LoRA adapters, see mlx-openai-server README for loading adapters or merge first, then serve."
```

Run it:

```bash
chmod +x bootstrap_ft.sh
./bootstrap_ft.sh
```

**Why these choices?**

- **MLX‑LoRA** CLI supports SFT with `messages` JSONL, LoRA/DoRA/full training types, and quantized training options (QLoRA). The flags we use are in its README examples (`--train-mode sft`, `--train-type lora`, `--data`, `--batch-size`, `--iters`, `--max-seq-length`, `--mask-prompt`, `--gradient-accumulation-steps`). [5](https://github.com/Goekdeniz-Guelmez/mlx-lm-lora)
- The **MLX‑community CodeLlama‑7B** repository provides an MLX‑friendly conversion and is known to run on Apple Silicon; it’s a good starting point if you haven’t been granted access to Meta’s HF repo yet. [6](https://huggingface.co/mlx-community/CodeLlama-7b-mlx)

> If you want the **Instruct** variant from Meta directly (`meta-llama/CodeLlama-7b-Instruct-hf`), request access on Hugging Face first. [7](https://huggingface.co/meta-llama/CodeLlama-7b-Instruct-hf)

---

## 1) What fits in **16 GB** and how to scale up

- Start with **seq length 1024**, **batch 1**, **grad accumulation 16**. Increase to 1536 later if stable.
- If you still hit memory pressure, drop `--max-seq-length` to **768–896** or reduce `--iters` for your first pass.
- If 7B feels tight for your use, MLX‑LoRA works with many smaller coder bases (e.g., Qwen‑2.5 Coder 3B Instruct), then you can scale back up to 7B. [5](https://github.com/Goekdeniz-Guelmez/mlx-lm-lora)

> We’re using MLX because it’s **native for Apple Silicon fine‑tuning**; CUDA‑centric stacks like `bitsandbytes` are not straightforward on macOS/MPS. [1](https://developer.apple.com/videos/play/wwdc2025/298/)[3](https://github.com/bitsandbytes-foundation/bitsandbytes/issues/252)

---

## 2) Quick local generation & evaluation

After training, do a spot check:

```bash
# (Option A) Generate with base to confirm it's running
mlx_lm.generate --model "mlx-community/CodeLlama-7b-mlx" \
  --prompt "Write a Python function that parses JSON and returns a flattened dict with dot notation."

# (Option B) Use your trained adapter   <-- see adapter instructions in your mlx-openai-server README
# e.g., serve the model with adapter and hit it via curl:
mlx-openai-server serve --model "mlx-community/CodeLlama-7b-mlx" --host 127.0.0.1 --port 8000
# then:
curl http://127.0.0.1:8000/v1/chat/completions \
  -H "Authorization: Bearer sk-local" -H "Content-Type: application/json" \
  -d '{"model":"mlx-codellama","messages":[{"role":"system","content":"You are a helpful Python coding assistant."},{"role":"user","content":"Create a Python dataclass for a config file loader using pathlib."}]}'
```

The **MLX OpenAI server** provides **OpenAI‑compatible endpoints** so existing clients, SDKs, and editor plugins can talk to it like the OpenAI API. It explicitly lists **LoRA adapter support** in its feature set; follow its README for the exact flag to attach your adapter or to serve a merged model directory. [8](https://pypi.org/project/mlx-openai-server/)[9](https://github.com/cubist38/mlx-openai-server)

---

## 3) VS Code integration (Continue)

Install **Continue** from the marketplace, then point it at your local OpenAI‑compatible server:

```yaml
# ~/.continue/config.yaml   (use "Continue: Open Config" in VS Code to edit)
models:
  - name: "MLX CodeLlama (local)"
    provider: "openai"
    model: "mlx-codellama"              # label shown inside Continue
    apiBase: "http://127.0.0.1:8000/v1" # matches mlx-openai-server
    apiKey: "sk-local-dummy"            # server accepts placeholder
    roles: ["chat", "autocomplete", "edit", "apply"]
```

Continue’s docs cover using local providers and custom endpoints; this is the standard OpenAI‑compatible setup. [10](https://docs.continue.dev/guides/ollama-guide)

> Alternative local stacks like **Ollama** also integrate with Continue, but since we’re training on **MLX**, keeping the same runtime simplifies things. [10](https://docs.continue.dev/guides/ollama-guide)

---

## 4) Dataset tips (Python‑centric quality)

- Keep prompt/response pairs **short and targeted** at tasks you care about (scripts, CLI tools, tests).
- Seed examples with your **preferred style** (type hints, `pathlib`, docstrings, minimal deps).
- Add a few **“explain then code”** examples — it helps instruction following and teach‑your‑style SFT.

(MLX examples and community guides illustrate preparing `messages` datasets and fine‑tuning flow on Apple Silicon.) [2](https://heidloff.net/article/apple-mlx-fine-tuning/)

---

## 5) Merging vs. adapters

- **Adapters (LoRA)**: smallest artifact; easiest to iterate.
- **Merge**: produces a single set of weights for serving anywhere.
The **MLX OpenAI server** supports running LoRA adapters; consult its README for the current adapter flag, or you can use project‑specific merge utilities if you prefer a single artifact. [8](https://pypi.org/project/mlx-openai-server/)

---

## 6) FAQ / Troubleshooting

- **“Why not QLoRA with bitsandbytes on Mac?”**
  `bitsandbytes` primarily targets CUDA/NVIDIA; macOS/MPS support has been limited and not plug‑and‑play. MLX is the Apple‑native path for training/inference. [3](https://github.com/bitsandbytes-foundation/bitsandbytes/issues/252)[4](https://huggingface.co/docs/bitsandbytes/main/en/installation)
- **“CodeLlama access needed?”**
  Yes for Meta’s official repos on HF; request access once. The **MLX‑community** hosts MLX‑converted variants that can help you get started. [7](https://huggingface.co/meta-llama/CodeLlama-7b-Instruct-hf)[6](https://huggingface.co/mlx-community/CodeLlama-7b-mlx)
- **“Does MLX actually support fine‑tuning?”**
  Yes — Apple’s WWDC session covers **fine‑tuning and quantization** on Apple Silicon, and multiple tutorials show end‑to‑end fine‑tuning with MLX. [1](https://developer.apple.com/videos/play/wwdc2025/298/)[2](https://heidloff.net/article/apple-mlx-fine-tuning/)

---

## What I can do next for you

1) Generate a **ready‑made Continue agent** that bakes in your style: “Prefer `pathlib`, add type hints, include tests.”
2) Create a **small curated dataset** from your own repos (we can redact) to better match your tasks.
3) Help you **switch bases** (e.g., a 3B coder for faster iteration) and later **scale back to 7B**.

Do you want me to:

- **(A)** run with the 7B settings above now, or
- **(B)** start with a ~3B coder for a faster first loop and then upgrade to 7B?

Either way, I’ll also add a **VS Code task** so you can run training and serving directly from the command palette.

Perfect—let’s start with **Plan B**: fine‑tune a **~3B coder** first for fast iteration on your **MacBook Air (16 GB)**, then scale up to 7B later using the same pipeline.

Below is a clean, copy‑pasteable path using **Apple’s MLX** (native for Apple Silicon) + **LoRA/QLoRA** fine‑tuning + **OpenAI‑compatible local server** + **VS Code (Continue)** integration.

---

## Why this stack (quickly)

- **MLX** runs and fine‑tunes LLMs efficiently on Apple Silicon; Apple’s docs and WWDC talk explicitly cover **fine‑tuning and quantization** on‑device. [1](https://developer.apple.com/videos/play/wwdc2025/298/)
- **Official `mlx_lm.lora`** CLI supports **LoRA** and **QLoRA**; if you pass a **quantized** model, it auto‑switches to QLoRA. It expects `train.jsonl`/`valid.jsonl` in **`messages`** chat format. [2](https://github.com/ml-explore/mlx-lm/blob/main/mlx_lm/LORA.md)[3](https://deepwiki.com/ml-explore/mlx-lm/4.1-lora-fine-tuning)
- CUDA‑centric QLoRA stacks (e.g., `bitsandbytes`) are designed for **NVIDIA CUDA** and are not straightforward on macOS/MPS—another reason we stick to MLX on a Mac. [4](https://github.com/bitsandbytes-foundation/bitsandbytes/issues/252)[5](https://huggingface.co/docs/bitsandbytes/main/en/installation)

---

## 0) Pick a small base coder

**Recommended (fast iteration):**

- **`Qwen2.5‑Coder‑3B‑Instruct`** family. You can use the vanilla HF model (`Qwen/Qwen2.5-Coder-3B-Instruct`) *or* an **MLX‑quantized variant** (e.g., `lmstudio-community/Qwen2.5-Coder-3B-Instruct-MLX-8bit`). Quantized MLX models run well on Apple Silicon, and passing a quantized model triggers **QLoRA** automatically in `mlx_lm.lora`. [6](https://huggingface.co/lmstudio-community/Qwen2.5-Coder-3B-Instruct-MLX-8bit)[2](https://github.com/ml-explore/mlx-lm/blob/main/mlx_lm/LORA.md)

> Qwen 2.5 includes a 3B size and instruct variants—good fit for a MacBook Air for quick loops. [7](https://github.com/mx4ai/qwen2.5)

---

## 1) Environment setup (once)

```bash
# macOS dev tools (ok if already installed)
xcode-select --install || true

# Python venv
python3 -m venv ~/.venvs/mlxft
source ~/.venvs/mlxft/bin/activate
python -m pip install -U pip

# Install MLX + training extras + data + serving
pip install -U "mlx-lm[train]" datasets huggingface_hub    # MLX + LoRA trainer
pip install -U mlx-openai-server                           # OpenAI-compatible local server (MLX)
```

- `mlx_lm.lora` is the **official MLX LoRA/QLoRA trainer**; `pip install "mlx-lm[train]"` provides the CLI and training deps. [2](https://github.com/ml-explore/mlx-lm/blob/main/mlx_lm/LORA.md)
- `mlx-openai-server` exposes **OpenAI‑compatible** endpoints for MLX models—you can point editors at `http://localhost:8000/v1`. [8](https://pypi.org/project/mlx-openai-server/)[9](https://github.com/cubist38/mlx-openai-server)

---

## 2) Prepare a tiny Python‑only dataset

We’ll create a minimal **instruction** dataset (chat `messages` JSONL) from **CodeAlpaca‑20k**, filtered to Python. This matches MLX’s expected format for SFT/LoRA. [2](https://github.com/ml-explore/mlx-lm/blob/main/mlx_lm/LORA.md)[3](https://deepwiki.com/ml-explore/mlx-lm/4.1-lora-fine-tuning)

```python
# prep_data.py
from pathlib import Path
from datasets import load_dataset
import json, random

OUT = Path("data/python-chat"); OUT.mkdir(parents=True, exist_ok=True)

ds = load_dataset("sahil2801/CodeAlpaca-20k", split="train")

def to_chat(ex):
    instr = (ex.get("instruction") or "").strip()
    inp   = (ex.get("input") or "").strip()
    outp  = (ex.get("output") or "").strip()
    if "python" not in (instr.lower() + " " + inp.lower() + " " + outp.lower()):
        return None
    user = instr if not inp else f"{instr}\n\nInput:\n{inp}"
    return {
        "messages": [
            {"role": "system", "content": "You are a helpful Python coding assistant. Prefer pathlib and type hints."},
            {"role": "user", "content": user},
            {"role": "assistant", "content": outp}
        ]
    }

records = [r for ex in ds if (r:=to_chat(ex))]
random.shuffle(records)
records = records[:2000]  # small first pass

n_test = max(100, len(records)//10)
test  = records[:n_test]
valid = records[n_test:2*n_test]
train = records[2*n_test:]

def dump(fn, rows):
    with open(OUT / fn, "w", encoding="utf-8") as f:
        for r in rows:
            f.write(json.dumps(r, ensure_ascii=False) + "\n")

dump("train.jsonl", train)
dump("valid.jsonl", valid)
dump("test.jsonl",  test)
print("Wrote:", OUT)
```

```bash
python prep_data.py
```

---

## 3) Fine‑tune with **LoRA/QLoRA** (MLX)

We’ll use a **quantized MLX** build of Qwen 3B coder to trigger QLoRA automatically, with small settings that are friendly for 16 GB. (If you use a **non‑quantized** model, it’ll do standard LoRA.) [2](https://github.com/ml-explore/mlx-lm/blob/main/mlx_lm/LORA.md)

```bash
# Choose ONE model:
#MODEL="Qwen/Qwen2.5-Coder-3B-Instruct"                 # base (LoRA)
MODEL="lmstudio-community/Qwen2.5-Coder-3B-Instruct-MLX-8bit"  # MLX-quantized (QLoRA)

DATA_DIR="data/python-chat"
OUT_DIR="outputs/qwen2_5coder3b-python-lora"
ITERS=800           # increase after you confirm the loop
SEQ_LEN=1024        # 768–1024 is comfy on 16 GB
BATCH=1
ACCUM=16
LR=1e-4

# LoRA/QLoRA training
mlx_lm.lora \
  --model "$MODEL" \
  --train \
  --data "$DATA_DIR" \
  --iters $ITERS \
  --max-seq-length $SEQ_LEN \
  --batch-size $BATCH \
  --gradient-accumulation-steps $ACCUM \
  --learning-rate $LR \
  --mask-prompt true \
  --adapter-path "$OUT_DIR"
```

- **CLI & flags** (e.g., `--model`, `--train`, `--data`, `--iters`, `--adapter-path`) come from the official MLX‑LM LoRA guide. **Passing quantized weights ⇒ QLoRA**; otherwise standard LoRA. The trainer expects `train.jsonl`/`valid.jsonl` and supports **`messages`** chat format with loss masking via `--mask-prompt`. [2](https://github.com/ml-explore/mlx-lm/blob/main/mlx_lm/LORA.md)[3](https://deepwiki.com/ml-explore/mlx-lm/4.1-lora-fine-tuning)

> If you hit memory pressure, reduce `--max-seq-length` to **768** and keep `batch-size=1` while increasing `gradient-accumulation-steps`. (This is a typical technique; the MLX docs outline dataset handling and loss masking; Apple’s WWDC session shows fine‑tuning/quantization on Apple Silicon.) [3](https://deepwiki.com/ml-explore/mlx-lm/4.1-lora-fine-tuning)[1](https://developer.apple.com/videos/play/wwdc2025/298/)

---

## 4) Quick local generation (sanity check)

```bash
# Sanity test with the base (or your adapter via fuse/generate; see docs)
mlx_lm.generate \
  --model "$MODEL" \
  --prompt "Write a Python function that flattens a nested dict into dot notation using pathlib if needed."
```

The `mlx_lm.generate` CLI is part of MLX‑LM and useful to verify the environment/model before wiring into VS Code. [2](https://github.com/ml-explore/mlx-lm/blob/main/mlx_lm/LORA.md)

---

## 5) Serve locally (OpenAI‑compatible) and wire up VS Code

**Start the server** (same model; we’ll add LoRA adapter support when you’re ready to persist the adapter path):

```bash
mlx-openai-server serve --model "$MODEL" --host 127.0.0.1 --port 8000
# OpenAI-compatible endpoints: http://127.0.0.1:8000/v1/...
```

The MLX OpenAI server provides **drop‑in OpenAI API endpoints** for local MLX models. (It also supports LoRA adapters—check the README for the adapter flag to point at your `$OUT_DIR`.) [8](https://pypi.org/project/mlx-openai-server/)[9](https://github.com/cubist38/mlx-openai-server)

**VS Code (Continue) configuration**

1. Install the **Continue** extension.
2. In VS Code, run **“Continue: Open Config”** and add an OpenAI provider pointing to your local MLX server:

```yaml
# ~/.continue/config.yaml
models:
  - name: "Qwen 3B Coder (MLX local)"
    provider: "openai"
    model: "mlx-qwen3b"                 # label in UI
    apiBase: "http://127.0.0.1:8000/v1" # MLX OpenAI server
    apiKey: "sk-local-dummy"            # placeholder (server accepts any)
    roles: ["chat", "autocomplete", "edit", "apply"]
```

Continue supports local providers and custom endpoints; using the **OpenAI** provider with a local API is a standard setup described in their guides (they show local backends like Ollama—same idea here with our OpenAI‑compatible MLX server). [10](https://docs.continue.dev/guides/ollama-guide)

---

## 6) Quality & iteration tips (Python‑centric)

- Keep data focused: short **Python** tasks, your preferred style (type hints, `pathlib`, tests).
- Add **explain‑then‑code** samples to improve instruction following.
- Once the loop works, increase `--iters` (e.g., 2–3k) and optionally bump `--max-seq-length` to 1280–1536 if stable.

(MLX LoRA docs cover configuration, dataset formats, prompt masking, and evaluation/generation flow.) [2](https://github.com/ml-explore/mlx-lm/blob/main/mlx_lm/LORA.md)[3](https://deepwiki.com/ml-explore/mlx-lm/4.1-lora-fine-tuning)

---

## 7) Upgrading to **7B** later

When you’re ready:

- Change `MODEL` to **CodeLlama‑7B** (MLX‑converted or Meta HF instruct).
  - MLX‑converted base: `mlx-community/CodeLlama-7b-mlx`. [11](https://huggingface.co/mlx-community/CodeLlama-7b-mlx)
  - Official instruct: `meta-llama/CodeLlama-7b-Instruct-hf` (request access first). [12](https://huggingface.co/meta-llama/CodeLlama-7b-Instruct-hf)
- Keep **batch=1**, start with **seq_len=1024**, and consider quantized MLX builds to leverage **QLoRA** automatically. (WWDC material shows fine‑tuning on Apple Silicon; `mlx_lm.lora` handles LoRA/QLoRA and dataset formats.) [1](https://developer.apple.com/videos/play/wwdc2025/298/)[2](https://github.com/ml-explore/mlx-lm/blob/main/mlx_lm/LORA.md)

---

## 8) Optional: a single “bootstrap” script

Want me to generate a `bootstrap_qwen3b.sh` that:

- creates the venv,
- installs deps,
- preps the dataset,
- runs LoRA/QLoRA fine‑tuning, then
- starts the MLX OpenAI server, and
- prints a **ready‑to‑paste** Continue config?

I can also add a **VS Code Task** so you can run training/serving from the command palette.

---

### Quick check‑in

Do you want me to:

1) **Produce the one‑shot script** and a **VS Code Task** now,
2) or **curate a mini dataset** from your own repos (to capture your conventions like `pathlib`, tests, typing) before training?
