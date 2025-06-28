// hooks/useListingPermissions.js
import { useSelector } from 'react-redux';
import { useMemo } from 'react';

export const useListingPermissions = (targetUser) => {
    const currentUser = useSelector(state => state.auth.user);

    return useMemo(() => {
        if (!currentUser || !targetUser) {
            return {
                canView: false,
                canEdit: false,
                canDelete: false,
                isOwner: false
            };
        }

        const isOwner = currentUser.id === targetUser.id;

        return {
            canView: true, // Tous les utilisateurs connectés peuvent voir
            canEdit: isOwner && currentUser.validationStatus?.identity?.isVerified,
            canDelete: isOwner,
            isOwner
        };
    }, [currentUser, targetUser]);
};