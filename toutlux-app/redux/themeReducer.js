import { createSlice } from '@reduxjs/toolkit';

const themeReducer = createSlice({
  name: 'theme',
  initialState: {
    isDarkMode: null,
  },
  reducers: {
    setDarkMode: (state, action) => {
      state.isDarkMode = action.payload;
    },
    toggleTheme: (state) => {
      state.isDarkMode = !state.isDarkMode;
    },
  },
});

export const { toggleTheme, setDarkMode } = themeReducer.actions;
export default themeReducer.reducer;