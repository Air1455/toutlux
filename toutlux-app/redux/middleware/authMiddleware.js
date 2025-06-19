import {authApi} from "@/redux/api/authApi";
import {logout, setAuth} from "@/redux/authSlice";
import {createListenerMiddleware} from "@reduxjs/toolkit";

const authMiddleware = createListenerMiddleware();

// Liste des endpoints qui ne nécessitent pas de refresh token
const publicEndpoints = ['register', 'check-user', 'login_check', 'auth/google', 'token/refresh'];

authMiddleware.startListening({
    matcher: (action) => {
        // Vérifie si c'est une action d'erreur d'une requête API
        if (!action.type?.endsWith('/rejected')) return false;

        // Ignore les endpoints publics
        return !publicEndpoints.some(ep => action.type.includes(ep));
    },
    effect: async (action, listenerApi) => {
        // Vérifie si c'est une erreur 401
        if (action.payload?.status === 401) {
            // Déconnexion immédiate si c'est un endpoint public
            if (publicEndpoints.some(ep => action.meta?.arg?.endpointName?.includes(ep))) {
                listenerApi.dispatch(logout());
                return;
            }

            const state = listenerApi.getState();
            const refreshToken = state.auth.refreshToken;

            if (!refreshToken) {
                listenerApi.dispatch(logout());
                return;
            }

            try {
                const refreshResult = await listenerApi.dispatch(
                    authApi.endpoints.refreshToken.initiate({ refresh_token: refreshToken })
                ).unwrap();

                if (refreshResult) {
                    listenerApi.dispatch(setAuth(refreshResult));
                }
            } catch (error) {
                listenerApi.dispatch(logout());
            }
        }
    },
});

export default authMiddleware;