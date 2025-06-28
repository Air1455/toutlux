import React from 'react';
import { TouchableOpacity, View, StyleSheet } from 'react-native';
import { useTheme } from 'react-native-paper';
import { MaterialCommunityIcons, Ionicons } from '@expo/vector-icons';

import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';

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

    const itemColor = isLogout ? colors.error : colors.textPrimary;
    const chevronColor = isLogout ? colors.error : colors.textSecondary;

    return (
        <TouchableOpacity
            style={[
                styles.menuItem,
                {
                    backgroundColor: colors.surface,
                    borderRadius: BORDER_RADIUS.lg
                }
            ]}
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
                <Text variant="bodyLarge" color={isLogout ? "error" : "textPrimary"} style={styles.menuText}>
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
        paddingVertical: SPACING.lg,
        paddingHorizontal: SPACING.sm,
        elevation: ELEVATION.high,
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
        marginRight: SPACING.lg,
        width: 24,
    },
    menuText: {
        flex: 1,
    },
    rightContent: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.sm,
    },
    statusIcon: {
        marginLeft: SPACING.sm,
    },
});