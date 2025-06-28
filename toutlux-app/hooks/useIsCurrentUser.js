import { useMemo } from 'react';
import { useSelector } from 'react-redux';
import { useGetMeQuery } from '@/redux/api/authApi';

/**
 * Utilitaire pour normaliser les IDs utilisateur
 */
export const normalizeUserId = (user) => {
    if (!user) return null;

    // Si c'est déjà un ID simple
    if (typeof user === 'number' || typeof user === 'string') {
        return user;
    }

    // Si c'est un objet utilisateur
    if (user.id) return user.id;

    // Si c'est un IRI API Platform
    if (user['@id']) {
        const parts = user['@id'].split('/');
        return parts[parts.length - 1];
    }

    return null;
};

/**
 * Hook pour comparer un utilisateur avec l'utilisateur connecté
 */
export const useCompareUser = (targetUser) => {
    const token = useSelector((state) => state.auth.token);
    const { data: currentUser, isLoading } = useGetMeQuery(undefined, {
        skip: !token
    });

    const isCurrentUser = useMemo(() => {
        if (!currentUser || !targetUser || isLoading) {
            return false;
        }

        const currentUserId = normalizeUserId(currentUser);
        const targetUserId = normalizeUserId(targetUser);

        return currentUserId && targetUserId && currentUserId === targetUserId;
    }, [currentUser, targetUser, isLoading]);

    return {
        isCurrentUser,
        currentUser,
        isLoading,
        hasAccess: isCurrentUser && !!token
    };
};

/**
 * Hook pour obtenir l'utilisateur connecté avec refetch
 */
export const useCurrentUser = () => {
    const token = useSelector((state) => state.auth.token);
    const isAuthenticated = useSelector((state) => state.auth.isAuthenticated);
    const storedUser = useSelector((state) => state.auth.user);

    const {
        data: user,
        isLoading,
        error,
        refetch
    } = useGetMeQuery(undefined, {
        skip: !token,
        refetchOnMountOrArgChange: true,
        refetchOnReconnect: true,
    });

    return {
        user: user || storedUser,
        userId: normalizeUserId(user || storedUser),
        isLoading,
        isAuthenticated: isAuthenticated && !!token,
        error,
        hasToken: !!token,
        refetch
    };
};

/**
 * Hook pour les permissions utilisateur
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
                isProfileComplete: false,
                completionPercentage: 0,
                missingFields: [],
                userType: null,
                status: null
            };
        }

        // Vérifier toutes les variantes possibles des noms de champs
        const isEmailVerified = user.isEmailVerified || user.emailVerified || false;
        const isPhoneVerified = user.isPhoneVerified || user.phoneVerified || false;
        const isIdentityVerified = user.isIdentityVerified || user.identityVerified || false;

        return {
            canCreateListing: user.isCanCreateListing || (
                isEmailVerified &&
                isPhoneVerified &&
                isIdentityVerified &&
                user.status === 'active'
            ),
            canViewPrivateInfo: true,
            canEditProfile: true,
            canAccessDashboard: user.status === 'active',
            isEmailVerified,
            isPhoneVerified,
            isIdentityVerified,
            isProfileComplete: user.isProfileComplete || user.profileComplete || false,
            completionPercentage: user.completionPercentage || user.profileCompletionPercentage || 0,
            missingFields: user.missingFields || user.profileMissingFields || [],
            userType: user.userType || user.type,
            status: user.status
        };
    }, [user, isAuthenticated]);

    return permissions;
};

/**
 * Hook pour gérer les permissions sur les annonces
 */
export const useListingPermissions = (targetUserId) => {
    const { user, userId, isAuthenticated } = useCurrentUser();
    const { canCreateListing } = useUserPermissions();

    const normalizedTargetId = normalizeUserId(targetUserId);

    const permissions = useMemo(() => {
        const isOwner = userId && normalizedTargetId && userId === normalizedTargetId;

        return {
            isOwner,
            canView: isOwner && isAuthenticated,
            canEdit: isOwner && canCreateListing,
            canDelete: isOwner && canCreateListing,
            canContact: !isOwner && isAuthenticated,
            canViewContactInfo: isOwner || (isAuthenticated && user?.isPremium)
        };
    }, [userId, normalizedTargetId, isAuthenticated, canCreateListing, user]);

    return permissions;
};