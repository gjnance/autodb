"""Auto-complete suggestions for relational fields."""

from fastapi import APIRouter, Depends, Query, Request
from fastapi.responses import HTMLResponse
from sqlalchemy.ext.asyncio import AsyncSession

from autodb.db import get_db
from autodb.dependencies import get_templates
from autodb.services import RelationService

router = APIRouter(tags=["suggest"])


@router.get("/api/suggest/{schema}/{table}/{column}", response_class=HTMLResponse)
async def get_suggestions(
    request: Request,
    schema: str,
    table: str,
    column: str,
    q: str = Query("", description="Search term"),
    db: AsyncSession = Depends(get_db),
) -> HTMLResponse:
    """Get auto-complete suggestions for a relational field.

    Args:
        schema: Database schema
        table: Source table name
        column: Column with relation rule
        q: Optional search term to filter results

    Returns:
        HTML list of suggestions
    """
    templates = get_templates()

    full_table = f"{schema}.{table}"
    service = RelationService(db)
    rule = await service.get_relation_for_column(full_table, column)

    if not rule:
        return templates.TemplateResponse(
            request,
            "partials/suggestions.html",
            {"suggestions": [], "column": column},
        )

    suggestions = await service.get_suggestions(rule, search_term=q, limit=15)

    return templates.TemplateResponse(
        request,
        "partials/suggestions.html",
        {"suggestions": suggestions, "column": column},
    )
