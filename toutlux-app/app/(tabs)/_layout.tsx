import React from 'react';
import { Tabs } from 'expo-router';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useTheme } from "react-native-paper";
import { useTranslation } from 'react-i18next';

const TAB_ICONS = {
    home: "magnify",
    inbox: "email-outline",
    profile: "account-outline",
    settings: "cog-outline"
};

const TabIcon = React.memo(({ name, color, size }) => (
    <MaterialCommunityIcons name={name} color={color} size={size} />
));

export default function TabsLayout() {
    const { colors } = useTheme();
    const { t } = useTranslation();

    const screenOptions = React.useMemo(() => ({
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.text,
        tabBarStyle: {
            backgroundColor: colors.background,
            height: 60,
        },
    }), [colors]);

    return (
        <Tabs screenOptions={screenOptions}>
            <Tabs.Screen
                name="home"
                options={{
                    tabBarIcon: (props) => (
                        <TabIcon name={TAB_ICONS.home} {...props} />
                    ),
                    title: t('tabs.search'),
                }}
            />
            <Tabs.Screen
                name="inbox"
                options={{
                    tabBarIcon: (props) => (
                        <TabIcon name={TAB_ICONS.inbox} {...props} />
                    ),
                    title: t('tabs.inbox'),
                }}
            />
            <Tabs.Screen
                name="profile"
                options={{
                    tabBarIcon: (props) => (
                        <TabIcon name={TAB_ICONS.profile} {...props} />
                    ),
                    title: t('tabs.profile'),
                }}
            />
            <Tabs.Screen
                name="settings"
                options={{
                    tabBarIcon: (props) => (
                        <TabIcon name={TAB_ICONS.settings} {...props} />
                    ),
                    title: t('tabs.settings'),
                }}
            />
        </Tabs>
    );
}