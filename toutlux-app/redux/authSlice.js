import { createSlice } from '@reduxjs/toolkit';

const initialState = {
    token: null,
    refreshToken: null, // ✅ AJOUT
    user: null,
    isAuthenticated: false,
};

const authSlice = createSlice({
    name: 'auth',
    initialState,
    reducers: {
        setAuth: (state, action) => {
            const { token, refresh_token, user } = action.payload;
            state.token = token;
            state.refreshToken = refresh_token; // ✅ AJOUT
            state.user = user;
            state.isAuthenticated = !!token;
        },
        setToken: (state, action) => {
            state.token = action.payload;
            state.isAuthenticated = !!action.payload;
        },
        setRefreshToken: (state, action) => { // ✅ AJOUT
            state.refreshToken = action.payload;
        },
        updateTokens: (state, action) => { // ✅ NOUVEAU: Pour refresh uniquement
            const { token, refresh_token } = action.payload;
            state.token = token;
            if (refresh_token) {
                state.refreshToken = refresh_token;
            }
            state.isAuthenticated = !!token;
        },
        logout: (state) => {
            state.token = null;
            state.refreshToken = null; // ✅ AJOUT
            state.user = null;
            state.isAuthenticated = false;
        },
        clearAuth: (state) => { // ✅ ALIAS pour logout
            state.token = null;
            state.refreshToken = null;
            state.user = null;
            state.isAuthenticated = false;
        },
    },
});

export const { setAuth, setToken, setRefreshToken, updateTokens, logout, clearAuth } = authSlice.actions;
export default authSlice.reducer;