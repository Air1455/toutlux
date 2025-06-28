import React from 'react';
import { Tabs } from 'expo-router';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useTheme } from "react-native-paper";
import { useTranslation } from 'react-i18next';
import { TYPOGRAPHY } from '@/utils/typography';
import { SPACING, ELEVATION } from '@/constants/spacing';
import { Platform } from 'react-native';

const TAB_ICONS = {
    home: "magnify",
    inbox: "email-outline",
    profile: "account-outline",
    settings: "cog-outline"
};

const TAB_HEIGHT = Platform.OS === 'ios' ? 85 : 70;
const ICON_SIZE = 24;

const TabIcon = React.memo(({ name, color, size = ICON_SIZE, focused }) => (
    <MaterialCommunityIcons
        name={focused ? name.replace('-outline', '') : name}
        color={color}
        size={size}
    />
));

export default function TabsLayout() {
    const { colors } = useTheme();
    const { t } = useTranslation();

    const screenOptions = React.useMemo(() => ({
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.textSecondary,
        tabBarStyle: {
            backgroundColor: colors.surface,
            borderTopColor: colors.outline,
            borderTopWidth: 0.5,
            height: TAB_HEIGHT,
            paddingTop: 0,
            paddingHorizontal: SPACING.sm,
            elevation: ELEVATION.high,
            shadowColor: colors.shadow,
            shadowOffset: { width: 0, height: -2 },
            shadowOpacity: 0.08,
            shadowRadius: 8,
            position: 'absolute',
        },
        tabBarLabelStyle: {
            ...TYPOGRAPHY.tabLabel,
            marginTop: 2,
            fontSize: 12,
            lineHeight: 16,
        },
        tabBarIconStyle: {
            marginBottom: 0,
        },
        tabBarItemStyle: {
            paddingVertical: SPACING.xs,
            borderRadius: 12,
            marginHorizontal: 2,
        },
        tabBarAllowFontScaling: false,
        tabBarHideOnKeyboard: Platform.OS === 'android',
        tabBarKeyboardHidesTabBar: true,
    }), [colors]);

    const getTabOptions = (iconName, titleKey) => ({
        tabBarIcon: ({ color, focused }) => (
            <TabIcon
                name={iconName}
                color={color}
                focused={focused}
                size={ICON_SIZE}
            />
        ),
        title: t(titleKey),
        tabBarAccessibilityLabel: t(titleKey),
    });

    return (
        <Tabs screenOptions={screenOptions}>
            <Tabs.Screen
                name="home"
                options={getTabOptions(TAB_ICONS.home, 'tabs.search')}
            />
            <Tabs.Screen
                name="inbox"
                options={getTabOptions(TAB_ICONS.inbox, 'tabs.inbox')}
            />
            <Tabs.Screen
                name="profile"
                options={getTabOptions(TAB_ICONS.profile, 'tabs.profile')}
            />
            <Tabs.Screen
                name="settings"
                options={getTabOptions(TAB_ICONS.settings, 'tabs.settings')}
            />
        </Tabs>
    );
}