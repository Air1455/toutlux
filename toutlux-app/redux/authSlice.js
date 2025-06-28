import { createSlice } from '@reduxjs/toolkit';

const initialState = {
    token: null,
    refreshToken: null,
    user: null,
    isAuthenticated: false,
    lastRefresh: null, // ✅ AJOUT: Track du dernier refresh
};

const authSlice = createSlice({
    name: 'auth',
    initialState,
    reducers: {
        setAuth: (state, action) => {
            const { token, refresh_token, user } = action.payload;
            console.log('🔑 Setting auth:', { hasToken: !!token, hasRefresh: !!refresh_token, userEmail: user?.email });

            state.token = token;
            state.refreshToken = refresh_token;
            state.user = user;
            state.isAuthenticated = !!token;
            state.lastRefresh = Date.now();
        },
        setToken: (state, action) => {
            console.log('🔑 Setting token:', !!action.payload);
            state.token = action.payload;
            state.isAuthenticated = !!action.payload;
        },
        setRefreshToken: (state, action) => {
            console.log('🔄 Setting refresh token:', !!action.payload);
            state.refreshToken = action.payload;
        },
        updateTokens: (state, action) => {
            const { token, refresh_token } = action.payload;
            console.log('🔄 Updating tokens:', {
                hasNewToken: !!token,
                hasNewRefresh: !!refresh_token,
                oldTokenExists: !!state.token
            });

            state.token = token;
            if (refresh_token) {
                state.refreshToken = refresh_token;
            }
            state.isAuthenticated = !!token;
            state.lastRefresh = Date.now();

            console.log('✅ Tokens updated successfully');
        },
        updateUser: (state, action) => {
            // ✅ AJOUT: Permettre la mise à jour des infos utilisateur
            if (state.isAuthenticated) {
                state.user = { ...state.user, ...action.payload };
            }
        },
        logout: (state) => {
            console.log('👋 Logging out user');
            state.token = null;
            state.refreshToken = null;
            state.user = null;
            state.isAuthenticated = false;
            state.lastRefresh = null;
        },
        clearAuth: (state) => {
            console.log('🧹 Clearing auth state');
            state.token = null;
            state.refreshToken = null;
            state.user = null;
            state.isAuthenticated = false;
            state.lastRefresh = null;
        },
    },
});

export const {
    setAuth,
    setToken,
    setRefreshToken,
    updateTokens,
    updateUser,
    logout,
    clearAuth
} = authSlice.actions;

export default authSlice.reducer;