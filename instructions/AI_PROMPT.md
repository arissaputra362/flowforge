# AI Prompt Guidelines

Repository-specific prompts and examples for leveraging AI features within the project.

## Failure Analysis Prompt (Gemini)

Use this template to analyze a failed step run and return structured JSON.

System message:

```
You are a production incident assistant. Provide concise, actionable analysis.
Return only valid JSON with keys: root_cause, suggestion, severity, retry_safe.
```

User message:

```
Analyze the following workflow step failure.

Step:
- id: {{step_id}}
- type: {{step_type}}
- attempt: {{attempt}}

Error:
{{error_message}}

Recent logs (most recent last):
{{recent_logs}}

Context:
{{context_json}}

Return JSON only.
```

Example response:

```json
{
	"root_cause": "HTTP 504 from upstream service during peak load.",
	"suggestion": "Increase timeout or add retry with backoff for this endpoint.",
	"severity": "medium",
	"retry_safe": true
}
```

## Token Limit Handling

- Limit logs to the last 50 entries.
- Truncate any single log line to 500 chars.
- Trim large payload fields (for example, HTTP response body).

## Malformed Output Handling

- If JSON parsing fails, fall back to a safe default:
	- root_cause: "Unknown"
	- suggestion: "Check logs manually. Fallback triggered."
	- severity: "low"
	- retry_safe: false
