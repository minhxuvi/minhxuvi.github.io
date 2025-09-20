# AI Learning Plan

## DeepSeek Architecture

<https://g.co/gemini/share/9c3678bc09d0>

Read the "Attention Is All You Need" paper the finish hand-on exercise like chatbot and llm-gateway-qa first.

This is an excellent goal. Learning about DeepSeek's architecture is a fantastic way to understand the cutting-edge of language model design. Its unique approach with Mixture-of-Experts and Multi-head Latent Attention showcases the future of efficient and scalable AI.

Here is a detailed learning plan to help you go from a beginner to having a deep understanding of DeepSeek's architecture, complete with citations and learning resources for each session.

---

### Phase 1: Foundational Knowledge (1-2 weeks)

**Goal:** Understand the basic building blocks of modern language models. You can't understand DeepSeek's innovations without first understanding the Transformer architecture it's built on.

#### **Session 1: The Transformer Architecture**

* **Concept:** The "Attention Is All You Need" paper introduced the Transformer, which is the foundational architecture for nearly all modern LLMs. Learn about its key components:
  * **Self-Attention:** How the model weighs the importance of different words in a sequence.
  * **Multi-Head Attention:** How it allows the model to pay attention to different parts of the input in parallel.
  * **Positional Encodings:** How the model understands the order of words since the self-attention mechanism is order-agnostic.
  * **Feed-Forward Networks (FFNs):** The non-attention part of the transformer block.
* **Learning Resources:**
  * **Paper:** "Attention Is All You Need" by Vaswani et al. (The original paper is a must-read, even if you just skim the high-level concepts).
  * **Video:** "The Illustrated Transformer" by Jay Alammar. This is the single best visual explanation of the Transformer architecture for newcomers.
  * **Blog Post:** "A Gentle Introduction to Transformers" by Hugging Face.

#### **Session 2: Dense vs. Sparse Models**

* **Concept:** Understand the fundamental difference between the dense architecture of Llama and the sparse MoE architecture of DeepSeek.
* **Learning Resources:**
  * **Blog Post:** "What is Mixture of Experts?" by IBM. This provides a great high-level overview of MoE.
  * **Video/Tutorial:** NVIDIA's "Applying Mixture of Experts in LLM Architectures" blog and accompanying videos. They explain the trade-offs and benefits in an accessible way.

### Phase 2: DeepSeek's Key Architectural Innovations (2-3 weeks)

**Goal:** Dive into the specific innovations that make DeepSeek unique. This is where you'll begin to understand how it achieves its efficiency and performance.

#### **Session 3: The DeepSeek Mixture-of-Experts (MoE) Architecture**

* **Concept:** Explore the details of DeepSeek's specialized MoE. Learn how its "router" works, the concept of **shared experts**, and how this differs from other MoE models like Mixtral.
* **Learning Resources:**
  * **Paper:** "DeepSeek-V2: A Strong, Economical, and Efficient Mixture-of-Experts Language Model" on arXiv. This is the primary source. Focus on the sections describing the DeepSeekMoE architecture.
  * **Technical Analysis:** "DeepSeek Technical Analysis — (1) Mixture-of-Experts" on Medium by Jinpeng Zhang. This is a fantastic breakdown of the paper's key ideas in a more digestible format.

#### **Session 4: Multi-head Latent Attention (MLA)**

* **Concept:** Understand how DeepSeek's novel attention mechanism works. Learn how it compresses the Key-Value (KV) cache to significantly reduce memory usage during inference, which is a major bottleneck for long context windows.
* **Learning Resources:**
  * **Paper:** The same "DeepSeek-V2" paper from Session 3. Look for the "Multi-head Latent Attention" section.
  * **Blog Post:** "Understanding Multi-Head Latent Attention" by Banatt. This article provides an excellent, easy-to-follow explanation of the concept of low-rank matrices and how they are used in MLA.

### Phase 3: Practical Application and Hands-on Experience (3-4 weeks)

**Goal:** Move from theory to practice. Work with the models directly using the Hugging Face ecosystem to solidify your understanding.

#### **Session 5: Navigating the Hugging Face Hub and Models**

* **Concept:** Get familiar with the Hugging Face ecosystem. Learn how to find DeepSeek's models on the Hub, understand the model cards, and use the `transformers` library to load them.
* **Learning Resources:**
  * **Hugging Face Documentation:** "Quickstart" on the Hugging Face Transformers documentation.
  * **Model Hub:** Explore the official DeepSeek models: `deepseek-ai/DeepSeek-V2` and `deepseek-ai/deepseek-moe-16b`. Pay attention to the files and their structure.

#### **Session 6: Running Inference with DeepSeek Models**

* **Concept:** Write your first lines of code. Use the `pipeline` function to perform inference (e.g., text generation, summarization) and then learn how to do it more manually with the tokenizer and model objects.
* **Learning Resources:**
  * **Hugging Face Notebook:** The official Hugging Face `transformers` library has excellent tutorials for inference.
  * **Google Colab/Jupyter Notebooks:** Create a notebook to experiment with different DeepSeek models. Pay attention to the memory usage (VRAM) and speed to see the efficiency benefits of the MoE architecture in action.

#### **Session 7: Fine-Tuning an LLM (with a focus on MoE)**

* **Concept:** Learn the process of fine-tuning an open-source LLM on a custom dataset. While full fine-tuning is resource-intensive, you can use **Parameter-Efficient Fine-Tuning (PEFT)** techniques like **LoRA** to practice on your own hardware.
* **Learning Resources:**
  * **Hugging Face Tutorial:** "Fine-tune a large language model with LoRA and Hugging Face." This guide walks you through the entire process step-by-step.
  * **Blog Post:** "Fine-Tuning LLMs: A Guide With Examples" by DataCamp. This provides a great conceptual overview before you dive into the code.

By following this plan, you will build a solid theoretical foundation and gain practical skills, giving you a deep and comprehensive understanding of the DeepSeek model architecture and its place in the world of modern AI.
