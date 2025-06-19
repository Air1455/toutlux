import React from 'react';
import { TouchableOpacity, View, StyleSheet } from 'react-native';
import { Text, useTheme } from 'react-native-paper';
import { MaterialCommunityIcons, Ionicons } from '@expo/vector-icons';

export const ProfileMenuItem = ({
                                    icon,
                                    title,
                                    onPress,
                                    iconFamily = 'MaterialCommunityIcons',
                                    isLogout = false,
                                    statusIcon = null,
                                    statusColor = null,
                                    showStatusIcon = false
                                }) => {
    const { colors } = useTheme();
    const IconComponent = iconFamily === 'Ionicons' ? Ionicons : MaterialCommunityIcons;

    const itemColor = isLogout ? colors.error : colors.onSurface;
    const chevronColor = isLogout ? colors.error : colors.onSurfaceVariant;

    return (
        <TouchableOpacity
            style={[styles.menuItem, {backgroundColor: colors.surface }]}
            onPress={onPress}
            activeOpacity={0.7}
        >
            <View style={styles.menuItemContent}>
                <IconComponent
                    name={icon}
                    size={24}
                    color={itemColor}
                    style={styles.menuIcon}
                />
                <Text style={[styles.menuText, { color: itemColor }]}>
                    {title}
                </Text>
            </View>

            <View style={styles.rightContent}>
                {showStatusIcon && statusIcon && (
                    <MaterialCommunityIcons
                        name={statusIcon}
                        size={20}
                        color={statusColor}
                        style={styles.statusIcon}
                    />
                )}
                {!isLogout && (
                    <MaterialCommunityIcons
                        name="chevron-right"
                        size={20}
                        color={chevronColor}
                    />
                )}
            </View>
        </TouchableOpacity>
    );
};

const styles = StyleSheet.create({
    menuItem: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingVertical: 16,
        paddingHorizontal: 10,
        borderRadius: 16,
        elevation: 4,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 8,
    },
    menuItemContent: {
        flexDirection: 'row',
        alignItems: 'center',
        flex: 1,
    },
    menuIcon: {
        marginRight: 16,
        width: 24,
    },
    menuText: {
        fontSize: 16,
        fontFamily: 'Prompt_400Regular',
        flex: 1,
    },
    rightContent: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
    },
    statusIcon: {
        marginLeft: 8,
    },
});