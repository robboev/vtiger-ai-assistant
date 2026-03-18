# vtiger-ai-assistant

AI Agent Assistant module for vtiger CRM 7/8. Self-evolving architecture: API-based tool execution for known actions, CLI agent builds new actions on demand.

## Architecture

- **module-src/**: vtiger installable module (builds to .zip)
- **cli-agent/**: background agent that generates new action classes from user requests
- **tests/**: unit + integration tests
- **specs/**: task spec files for orchestrator

## Key Concepts

- **Actions**: PHP classes extending `AIAction_Base` that execute CRM operations
- **Tool Definitions**: JSON schemas describing actions for Claude API tool_use
- **ActionRegistry**: auto-discovers and loads all available actions
- **ActionExecutor**: sandboxed runner with time/record/rate limits
- **AgentQueue**: queues unknown requests for CLI agent to build new actions

## Build

```bash
bash build.sh    # produces vtiger-ai-assistant.zip
```

## Multi-Tenant

Works with vtiger multi-tenant setup. Each tenant gets:
- Own conversation history (vtiger_ai_conversations table)
- Own audit log (vtiger_ai_audit_log table)
- Shared action library (actions are tenant-agnostic)

## Safety Layers

1. Worktree sandbox (CLI agent)
2. Static code analysis (forbidden patterns)
3. Runtime sandbox (time/record/write limits)
4. Prompt injection protection (tool schema validation)
5. Approval pipeline (auto-test + admin review)
6. Audit log + kill switch
