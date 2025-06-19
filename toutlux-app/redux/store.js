import { combineReducers, configureStore } from '@reduxjs/toolkit';
import generalReducer from './generalReducer';
import houseFilterReducer from '@/redux/houseFilterReducer';
import themeReducer from '@/redux/themeReducer';
import authReducer from './authSlice';

import { houseApi } from './api/houseApi';
import { userApi } from './api/userApi';
import { authApi } from './api/authApi';
import AsyncStorage from "@react-native-async-storage/async-storage";
import { persistReducer, persistStore, FLUSH, REHYDRATE, PAUSE, PERSIST, PURGE, REGISTER } from "redux-persist";
import authMiddleware from "@/redux/middleware/authMiddleware";

const authPersistConfig = {
    key: 'auth',
    storage: AsyncStorage,
    whitelist: ['token', 'refreshToken', 'user', 'isAuthenticated'], // ✅ AJOUT refreshToken
};

// ✅ PERSIST seulement le slice auth
const persistedAuthReducer = persistReducer(authPersistConfig, authReducer);

const rootReducer = combineReducers({
    general: generalReducer,
    houseFilter: houseFilterReducer,
    theme: themeReducer,
    auth: persistedAuthReducer, // ✅ Seulement auth est persisté
    [houseApi.reducerPath]: houseApi.reducer,
    [userApi.reducerPath]: userApi.reducer,
    [authApi.reducerPath]: authApi.reducer,
});

export const store = configureStore({
    reducer: rootReducer,
    middleware: (getDefaultMiddleware) =>
        getDefaultMiddleware({
            serializableCheck: {
                ignoredActions: [FLUSH, REHYDRATE, PAUSE, PERSIST, PURGE, REGISTER],
            },
            immutableCheck: {
                ignoredPaths: ['auth.token', 'auth.refreshToken'], // Ignorer pour les tokens
            },
        }).concat(
            houseApi.middleware,
            userApi.middleware,
            authApi.middleware,
            authMiddleware.middleware
        ),
});

export const persistor = persistStore(store);