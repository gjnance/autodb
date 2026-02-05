"""FastAPI dependencies and shared utilities."""

from functools import lru_cache
from pathlib import Path

from fastapi.templating import Jinja2Templates

from autodb.config import Settings, get_settings


@lru_cache
def get_templates() -> Jinja2Templates:
    """Get Jinja2 templates instance."""
    templates_dir = Path(__file__).parent / "templates"
    templates = Jinja2Templates(directory=str(templates_dir))

    # Add custom filters
    templates.env.filters["truncate_display"] = truncate_display

    # Add globals
    settings = get_settings()
    templates.env.globals["settings"] = settings

    return templates


def truncate_display(value: str | None, max_length: int = 100) -> str:
    """Truncate a string for display, adding ellipsis if needed."""
    if value is None:
        return ""
    value_str = str(value)
    if len(value_str) <= max_length:
        return value_str
    return value_str[: max_length - 3] + "..."
