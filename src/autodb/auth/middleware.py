"""Authentication middleware and dependencies."""

from collections.abc import Callable
from typing import Any

from fastapi import Request
from itsdangerous import BadSignature, URLSafeTimedSerializer
from starlette.middleware.base import BaseHTTPMiddleware
from starlette.responses import Response

from autodb.auth.azure_ad import User
from autodb.config import get_settings


class AuthMiddleware(BaseHTTPMiddleware):
    """Middleware for handling session-based authentication."""

    def __init__(self, app: Any, settings: Any = None) -> None:
        super().__init__(app)
        self.settings = settings or get_settings()
        self.serializer = URLSafeTimedSerializer(self.settings.secret_key)

    async def dispatch(self, request: Request, call_next: Callable) -> Response:  # type: ignore[type-arg]
        """Process request and attach user to request state."""
        request.state.user = None

        # Try to get user from session cookie
        session_cookie = request.cookies.get(self.settings.session_cookie_name)
        if session_cookie:
            try:
                # Deserialize session data (valid for 7 days)
                user_data = self.serializer.loads(session_cookie, max_age=604800)
                request.state.user = User(**user_data)
            except BadSignature:
                # Invalid or expired session
                pass

        response = await call_next(request)
        return response

    def create_session_cookie(self, user: User) -> str:
        """Create a signed session cookie value for a user."""
        user_data = {
            "id": user.id,
            "name": user.name,
            "email": user.email,
            "preferred_username": user.preferred_username,
            "role": user.role,
        }
        return self.serializer.dumps(user_data)


def get_current_user(request: Request) -> User | None:
    """FastAPI dependency to get current authenticated user.

    Usage:
        @app.get("/protected")
        async def protected_route(user: User | None = Depends(get_current_user)):
            if not user:
                raise HTTPException(status_code=401)
            return {"user": user.name}
    """
    return getattr(request.state, "user", None)
