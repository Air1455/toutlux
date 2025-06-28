import React, { createContext, useContext, useEffect, useState } from 'react';
import { View, StyleSheet } from 'react-native';
import NetInfo from '@react-native-community/netinfo';
import { useTheme, Portal, Modal, Button } from 'react-native-paper';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import Text from '@/components/typography/Text';
import { SPACING } from '@/constants/spacing';
import { useTranslation } from 'react-i18next';

const NetworkContext = createContext({
    isConnected: true,
    isInternetReachable: true,
    connectionType: null,
});

export const useNetwork = () => useContext(NetworkContext);

export const NetworkProvider = ({ children }) => {
    const [isConnected, setIsConnected] = useState(true);
    const [isInternetReachable, setIsInternetReachable] = useState(true);
    const [connectionType, setConnectionType] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const { colors } = useTheme();
    const { t } = useTranslation();

    useEffect(() => {
        // Écouter les changements de connexion
        const unsubscribe = NetInfo.addEventListener(state => {
            console.log('Network state changed:', state);

            setIsConnected(state.isConnected ?? false);
            setIsInternetReachable(state.isInternetReachable ?? false);
            setConnectionType(state.type);

            // Afficher la modal si pas de connexion
            if (!state.isConnected || !state.isInternetReachable) {
                setShowModal(true);
            } else {
                setShowModal(false);
            }
        });

        // Vérifier l'état initial
        NetInfo.fetch().then(state => {
            console.log('Initial network state:', state);
            setIsConnected(state.isConnected ?? false);
            setIsInternetReachable(state.isInternetReachable ?? false);
            setConnectionType(state.type);

            if (!state.isConnected || !state.isInternetReachable) {
                setShowModal(true);
            }
        });

        return () => unsubscribe();
    }, []);

    const handleRetry = async () => {
        const state = await NetInfo.fetch();
        if (state.isConnected && state.isInternetReachable) {
            setShowModal(false);
            // La modal se fermera automatiquement et les composants
            // qui utilisent refetchOnReconnect se rafraîchiront
        }
    };

    return (
        <NetworkContext.Provider value={{ isConnected, isInternetReachable, connectionType }}>
            {children}

            <Portal>
                <Modal
                    visible={showModal}
                    dismissable={false}
                    contentContainerStyle={[styles.modalContent, { backgroundColor: colors.surface }]}
                >
                    <View style={styles.modalInner}>
                        <MaterialCommunityIcons
                            name="wifi-off"
                            size={80}
                            color={colors.error}
                            style={styles.icon}
                        />

                        <Text variant="pageTitle" color="textPrimary" style={styles.title}>
                            {t('network.noConnection')}
                        </Text>

                        <Text variant="bodyLarge" color="textSecondary" style={styles.description}>
                            {t('network.checkConnection')}
                        </Text>

                        <View style={styles.statusContainer}>
                            <View style={styles.statusRow}>
                                <Text variant="bodyMedium" color="textSecondary">
                                    {t('network.status')}:
                                </Text>
                                <View style={[styles.statusIndicator, {
                                    backgroundColor: isConnected ? colors.success : colors.error
                                }]} />
                                <Text variant="bodyMedium" color={isConnected ? 'success' : 'error'}>
                                    {isConnected ? t('network.connected') : t('network.disconnected')}
                                </Text>
                            </View>

                            {connectionType && (
                                <Text variant="bodySmall" color="textSecondary" style={styles.connectionType}>
                                    {t('network.type')}: {connectionType}
                                </Text>
                            )}
                        </View>

                        <Button
                            mode="contained"
                            onPress={handleRetry}
                            style={styles.retryButton}
                            icon="refresh"
                        >
                            {t('network.retry')}
                        </Button>
                    </View>
                </Modal>
            </Portal>
        </NetworkContext.Provider>
    );
};

// Composant d'indicateur de connexion (optionnel)
export const NetworkIndicator = () => {
    const { isConnected, connectionType } = useNetwork();
    const { colors } = useTheme();
    const { t } = useTranslation();
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        if (!isConnected) {
            setVisible(true);
        } else {
            const timer = setTimeout(() => setVisible(false), 3000);
            return () => clearTimeout(timer);
        }
    }, [isConnected]);

    if (!visible) return null;

    return (
        <View style={[
            styles.indicator,
            { backgroundColor: isConnected ? colors.success : colors.error }
        ]}>
            <MaterialCommunityIcons
                name={isConnected ? "wifi" : "wifi-off"}
                size={16}
                color="white"
            />
            <Text variant="bodySmall" style={styles.indicatorText}>
                {isConnected ? t('network.reconnected') : t('network.offline')}
            </Text>
        </View>
    );
};

const styles = StyleSheet.create({
    modalContent: {
        margin: SPACING.xl,
        borderRadius: 16,
        padding: SPACING.xl,
        maxWidth: 400,
        alignSelf: 'center',
        width: '90%',
    },
    modalInner: {
        alignItems: 'center',
    },
    icon: {
        marginBottom: SPACING.lg,
    },
    title: {
        textAlign: 'center',
        marginBottom: SPACING.md,
    },
    description: {
        textAlign: 'center',
        marginBottom: SPACING.xl,
        lineHeight: 24,
    },
    statusContainer: {
        marginBottom: SPACING.xl,
        alignItems: 'center',
    },
    statusRow: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.sm,
    },
    statusIndicator: {
        width: 12,
        height: 12,
        borderRadius: 6,
    },
    connectionType: {
        marginTop: SPACING.xs,
    },
    retryButton: {
        minWidth: 150,
    },
    indicator: {
        position: 'absolute',
        top: 0,
        left: 0,
        right: 0,
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        paddingVertical: SPACING.xs,
        paddingHorizontal: SPACING.md,
        gap: SPACING.xs,
        zIndex: 9999,
    },
    indicatorText: {
        color: 'white',
        fontWeight: '600',
    },
});

// Hook pour gérer les erreurs réseau dans les requêtes
export const useNetworkError = () => {
    const { isConnected, isInternetReachable } = useNetwork();

    const checkNetwork = () => {
        if (!isConnected || !isInternetReachable) {
            throw new Error('No internet connection');
        }
    };

    return { checkNetwork, isConnected, isInternetReachable };
};