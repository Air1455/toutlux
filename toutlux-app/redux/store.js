import { combineReducers, configureStore } from '@reduxjs/toolkit';
import generalReducer from './generalReducer';
import houseFilterReducer from '@/redux/houseFilterReducer';
import themeReducer from '@/redux/themeReducer';
import authReducer from './authSlice';

import { houseApi } from '@/redux/api/houseApi';
import { userApi } from '@/redux/api/userApi';
import { authApi } from '@/redux/api/authApi';
import { contactApi } from '@/redux/api/contactApi';
import AsyncStorage from "@react-native-async-storage/async-storage";
import {
    persistReducer,
    persistStore,
    FLUSH,
    REHYDRATE,
    PAUSE,
    PERSIST,
    PURGE,
    REGISTER
} from "redux-persist";
import {notificationApi} from "@/redux/api/notificationApi";

// ✅ Persist config pour auth
const authPersistConfig = {
    key: 'auth',
    storage: AsyncStorage,
    whitelist: ['token', 'refreshToken', 'user', 'isAuthenticated'],
};

const persistedAuthReducer = persistReducer(authPersistConfig, authReducer);

const rootReducer = combineReducers({
    general: generalReducer,
    houseFilter: houseFilterReducer,
    theme: themeReducer,
    auth: persistedAuthReducer,
    [houseApi.reducerPath]: houseApi.reducer,
    [userApi.reducerPath]: userApi.reducer,
    [authApi.reducerPath]: authApi.reducer,
    [notificationApi.reducerPath]: notificationApi.reducer,
    [contactApi.reducerPath]: contactApi.reducer,
});

export const store = configureStore({
    reducer: rootReducer,
    middleware: (getDefaultMiddleware) =>
        getDefaultMiddleware({
            serializableCheck: {
                ignoredActions: [FLUSH, REHYDRATE, PAUSE, PERSIST, PURGE, REGISTER],
            },
            immutableCheck: {
                ignoredPaths: ['auth.token', 'auth.refreshToken'],
            },
        }).concat(
            houseApi.middleware,
            userApi.middleware,
            authApi.middleware,
            notificationApi.middleware,
            contactApi.middleware
        ),
});

export const persistor = persistStore(store);