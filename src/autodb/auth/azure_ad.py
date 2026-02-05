"""Azure AD OAuth2 authentication using MSAL."""

from dataclasses import dataclass

import msal

from autodb.config import get_settings


@dataclass
class User:
    """Authenticated user information."""

    id: str
    name: str
    email: str
    preferred_username: str
    role: str = "viewer"  # Default role for authenticated users


class AzureADAuth:
    """Azure AD authentication handler using MSAL."""

    def __init__(self) -> None:
        self.settings = get_settings()
        self._msal_app: msal.ConfidentialClientApplication | None = None

    @property
    def msal_app(self) -> msal.ConfidentialClientApplication:
        """Get or create MSAL application instance."""
        if self._msal_app is None:
            self._msal_app = msal.ConfidentialClientApplication(
                client_id=self.settings.azure_client_id,
                client_credential=self.settings.azure_client_secret,
                authority=self.settings.azure_authority,
            )
        return self._msal_app

    def get_auth_url(self, state: str | None = None) -> str:
        """Generate Azure AD authorization URL.

        Args:
            state: Optional state parameter for CSRF protection

        Returns:
            URL to redirect user to for authentication
        """
        return self.msal_app.get_authorization_request_url(
            scopes=self.settings.azure_scopes,
            redirect_uri=self.settings.azure_redirect_uri,
            state=state,
        )

    async def get_token_from_code(self, code: str) -> dict | None:
        """Exchange authorization code for tokens.

        Args:
            code: Authorization code from Azure AD callback

        Returns:
            Token response containing access_token and id_token, or None on error
        """
        result = self.msal_app.acquire_token_by_authorization_code(
            code=code,
            scopes=self.settings.azure_scopes,
            redirect_uri=self.settings.azure_redirect_uri,
        )

        if "error" in result:
            return None

        return result

    def parse_id_token(self, token_response: dict) -> User | None:
        """Parse user information from ID token claims.

        Args:
            token_response: Token response from Azure AD

        Returns:
            User object with parsed information, or None on error
        """
        claims = token_response.get("id_token_claims", {})

        if not claims:
            return None

        return User(
            id=claims.get("oid", claims.get("sub", "")),
            name=claims.get("name", ""),
            email=claims.get("email", claims.get("preferred_username", "")),
            preferred_username=claims.get("preferred_username", ""),
        )

    def get_logout_url(self, post_logout_redirect_uri: str | None = None) -> str:
        """Generate Azure AD logout URL.

        Args:
            post_logout_redirect_uri: URL to redirect to after logout

        Returns:
            URL to redirect user to for logout
        """
        url = f"{self.settings.azure_authority}/oauth2/v2.0/logout"
        if post_logout_redirect_uri:
            url += f"?post_logout_redirect_uri={post_logout_redirect_uri}"
        return url
