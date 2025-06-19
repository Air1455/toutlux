import { createSlice } from '@reduxjs/toolkit';
import {jwtDecode} from "jwt-decode";
import AsyncStorage from "@react-native-async-storage/async-storage";

const initialSlice = createSlice({
    name: 'general',
    initialState: {
        token: null,
        refreshToken: null,
        user: null,
        me: null,
        loggedIn: false,
        isFirstVisit: true,
    },
    reducers: {
        setToken: (state, action) => {
            if(action.payload){
                const decodedToken= jwtDecode(action.payload);
                state.me= `/api/publishers/${decodedToken['publisher_id']}`;
                state.user= `/users/${decodedToken['user_id']}`;
                AsyncStorage.setItem('authToken', action.payload)
            } else{
                state.me= null;
                state.user= null;
                state.refreshToken= null;
                AsyncStorage.removeItem('authToken')
                AsyncStorage.removeItem('refreshToken')
            }
            state.token= action.payload;
            state.loggedIn = !!state.token
        },
        setRefreshToken: (state, action) => {
            state.refreshToken= action.payload;
            AsyncStorage.setItem('refreshToken', action.payload)
        }
    }
});

export const { setToken, setRefreshToken } = initialSlice.actions;
export default initialSlice.reducer;