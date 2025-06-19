import { useSelector } from 'react-redux';
import { useColorScheme } from 'react-native';
import { customDarkTheme, customLightTheme } from '@/utils/customTheme';

const useAppTheme = () => {
    const isDarkMode = useSelector((state) => state.theme.isDarkMode);
    const colorScheme = useColorScheme();
    const systemIsDark = colorScheme === 'dark';
    const finalIsDarkMode = isDarkMode ?? systemIsDark;
    const theme = finalIsDarkMode ? customDarkTheme : customLightTheme;

    return { isDarkMode: finalIsDarkMode, theme };
};

export default useAppTheme;