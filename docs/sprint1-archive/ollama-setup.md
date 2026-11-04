# Ollama Setup Guide for A-FIRST Development

## Installation

Download and install Ollama for Windows from https://ollama.com

## Pull the required model

```bash
ollama pull llama3.2:1b
```

## Verify the model is running

```bash
ollama list
```

Expected output:
```
NAME           ID              SIZE      MODIFIED
llama3.2:1b    baf6a787fdff    1.3 GB    recent
```

## Test the API

```bash
curl -X POST http://localhost:11434/api/generate \
  -H "Content-Type: application/json" \
  -d "{\"model\":\"llama3.2:1b\",\"prompt\":\"What is DHIS2?\",\"stream\":false}"
```

A valid response contains a `response` key with the AI answer.

## System requirements

- Minimum 2GB free RAM for llama3.2:1b
- Minimum 4GB free RAM for llama3.2:3b
- Minimum 8GB free RAM for llama3.1:8b

## Production fallback

For production on Africa CDC servers, configure the Claude API endpoint:

- Base URL: https://api.anthropic.com/v1
- Model: claude-sonnet-4-6
- Authentication: Bearer token via ANTHROPIC_API_KEY environment variable

Update the model name in block_ollama_chat.php and point the fetch URL to the Claude API endpoint when deploying to production.
