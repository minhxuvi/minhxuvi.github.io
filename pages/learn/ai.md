# Learn About AI

## Sigmoid Function

The sigmoid function is defined as:

```math
\sigma(x) = \frac{1}{1 + e^{-x}}
```

It maps any real-valued number into the range (0, 1).

Why is "e"?
<https://www.youtube.com/watch?v=1GqYpmLjTRQ&t=1s>

- We can choose any number bigger than 1. But make the sigmoid function more simple, especially when doing differencial.

## Deep Learning and Neural Networks

<https://www.youtube.com/watch?v=BR9h47Jtqyw>

- Minimal error
- Neural Network
  - Sigmoid function to calculate propability.
  - Product of the propability to get maximum likelihood.
  - Turn Product to Sum to advoid small number, easier to do calculation. Multiplication is an algorithm built on top of addition.
  - Logistic regression
  - Real neuron include: Dendrites (multi-input), Nucleus (Process), Axon (Single output)
- Non-linear region by combining regions with weight.
- Constant vs coefficients vs variables in ax + by = c
- Deep Neural Network is Neural Network with many hidden layers.

## Recurrent Neural Networks

<https://www.youtube.com/watch?v=UNmqTiOnRfg>

- Matrix multiplication
  - The number of collumn in 1st matrix must == no of row in 2nd matrix
  - AxB != BxA
  - Size: The result will be (# rows in 1st) × (# cols in 2nd).
  - Calculate: To find the element in position [i, j] of the result, take the dot product of row i from the first matrix and column j from the second matrix.

## "Attention Is All You Need"

<https://jalammar.github.io/visualizing-neural-machine-translation-mechanics-of-seq2seq-models-with-attention/>
<https://jalammar.github.io/illustrated-transformer/>
<https://arxiv.org/abs/1706.03762>

## Hands-on

<https://chatgpt.com/share/68ceb9e0-3378-8006-81ff-d056700b5e87>
<https://grok.com/share/c2hhcmQtNA%3D%3D_e98b1933-5a44-4044-ba4b-013b4ac1c6af>
<https://github.com/minhxuvi/llm-gateway-qa>
