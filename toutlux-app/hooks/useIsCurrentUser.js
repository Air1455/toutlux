import { useMemo } from 'react';
import { useSelector } from 'react-redux';
import { useGetMeQuery } from '@/redux/api/authApi';

/**
 * Hook pour vérifier si un utilisateur donné est l'utilisateur connecté
 * @param {Object} targetUser - L'utilisateur à comparer
 * @returns {Object} { isCurrentUser: boolean, currentUser: Object, isLoading: boolean, hasAccess: boolean }
 */
export const useIsCurrentUser = (targetUser) => {
    const token = useSelector((state) => state.auth.token);

    // ✅ CORRECTION: Utiliser useGetMeQuery au lieu de useGetMyProfileQuery
    const { data: currentUser, isLoading } = useGetMeQuery(undefined, {
        skip: !token
    });

    const isCurrentUser = useMemo(() => {
        if (!currentUser || !targetUser || isLoading) {
            return false;
        }

        // Méthode 1: Comparer par ID (le plus fiable)
        if (currentUser.id && targetUser.id) {
            return currentUser.id === targetUser.id;
        }

        // Méthode 2: Comparer par email (fallback)
        if (currentUser.email && targetUser.email) {
            return currentUser.email.toLowerCase() === targetUser.email.toLowerCase();
        }

        // Méthode 3: Comparer par nom d'utilisateur (si disponible)
        if (currentUser.username && targetUser.username) {
            return currentUser.username === targetUser.username;
        }

        return false;
    }, [currentUser, targetUser, isLoading]);

    return {
        isCurrentUser,
        currentUser,
        isLoading,
        hasAccess: isCurrentUser && !!token
    };
};

/**
 * Hook simplifié pour obtenir l'utilisateur connecté
 * @returns {Object} { user: Object, isLoading: boolean, isAuthenticated: boolean, error: Object }
 */
export const useCurrentUser = () => {
    const token = useSelector((state) => state.auth.token);
    const isAuthenticated = useSelector((state) => state.auth.isAuthenticated);
    const storedUser = useSelector((state) => state.auth.user);

    const { data: user, isLoading, error } = useGetMeQuery(undefined, {
        skip: !token,
        // ✅ AJOUT: Retry automatique si échec
        refetchOnMountOrArgChange: true,
        refetchOnReconnect: true,
    });

    return {
        user: user || storedUser, // ✅ Fallback sur user stocké
        isLoading,
        isAuthenticated: isAuthenticated && !!token,
        error,
        hasToken: !!token
    };
};

/**
 * Hook pour vérifier les permissions d'un utilisateur
 * @returns {Object} Objets avec différentes permissions
 */
export const useUserPermissions = () => {
    const { user, isAuthenticated } = useCurrentUser();

    const permissions = useMemo(() => {
        if (!user || !isAuthenticated) {
            return {
                canCreateListing: false,
                canViewPrivateInfo: false,
                canEditProfile: false,
                canAccessDashboard: false,
                isEmailVerified: false,
                isPhoneVerified: false,
                isIdentityVerified: false,
                isProfileComplete: false
            };
        }

        return {
            // Permissions basées sur les vérifications
            canCreateListing: user.isCanCreateListing || (
                user.isEmailVerified &&
                user.isPhoneVerified &&
                user.isIdentityVerified &&
                user.status === 'active'
            ),
            canViewPrivateInfo: true,
            canEditProfile: true,
            canAccessDashboard: user.status === 'active',

            // États de vérification
            isEmailVerified: user.isEmailVerified || false,
            isPhoneVerified: user.isPhoneVerified || false,
            isIdentityVerified: user.isIdentityVerified || false,
            isProfileComplete: user.isProfileComplete || false,

            // Informations supplémentaires
            completionPercentage: user.completionPercentage || 0,
            missingFields: user.missingFields || [],
            userType: user.userType,
            status: user.status
        };
    }, [user, isAuthenticated]);

    return permissions;
};

/**
 * Hook pour comparer deux utilisateurs
 * @param {Object} user1 - Premier utilisateur
 * @param {Object} user2 - Deuxième utilisateur
 * @returns {Object} Résultat de la comparaison
 */
export const useCompareUsers = (user1, user2) => {
    const comparison = useMemo(() => {
        if (!user1 || !user2) {
            return {
                areEqual: false,
                sameId: false,
                sameEmail: false,
                sameUsername: false
            };
        }

        const sameId = user1.id === user2.id;
        const sameEmail = user1.email?.toLowerCase() === user2.email?.toLowerCase();
        const sameUsername = user1.username === user2.username;

        return {
            areEqual: sameId || sameEmail || sameUsername,
            sameId,
            sameEmail,
            sameUsername,
            confidence: sameId ? 'high' : sameEmail ? 'medium' : sameUsername ? 'low' : 'none'
        };
    }, [user1, user2]);

    return comparison;
};