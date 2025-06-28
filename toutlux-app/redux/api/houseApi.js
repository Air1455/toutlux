import { createApi } from '@reduxjs/toolkit/query/react';
import { createBaseQueryWithReauth } from './baseQuery';

export const houseApi = createApi({
    reducerPath: 'houseApi',
    baseQuery: createBaseQueryWithReauth(),
    tagTypes: ['House', 'UserHouses'],
    refetchOnReconnect: true, // ✅ Ajout pour refetch à la reconnexion
    endpoints: (builder) => ({
        // Récupérer toutes les annonces (publiques) - SANS AUTH
        getHouses: builder.query({
            query: (params = {}) => {
                const searchParams = new URLSearchParams();

                // Paramètres de filtrage
                if (params.city) searchParams.append('city', params.city);
                if (params.type) searchParams.append('type', params.type);
                if (params.minPrice) searchParams.append('price[gte]', params.minPrice);
                if (params.maxPrice) searchParams.append('price[lte]', params.maxPrice);
                if (params.bedrooms) searchParams.append('bedrooms', params.bedrooms);
                if (params.bathrooms) searchParams.append('bathrooms', params.bathrooms);
                if (params.isForRent !== undefined) searchParams.append('isForRent', params.isForRent);
                if (params.page) searchParams.append('page', params.page);
                if (params.itemsPerPage) searchParams.append('itemsPerPage', params.itemsPerPage);

                const queryString = searchParams.toString();
                return `houses${queryString ? `?${queryString}` : ''}`;
            },
            transformResponse: (response) => {
                if (response.member) {
                    return {
                        data: response.member,
                        totalItems: response.totalItems || 0,
                        pagination: {
                            currentPage: response.view?.['@id']?.match(/page=(\d+)/)?.[1] || 1,
                            totalItems: response.totalItems || 0,
                            itemsPerPage: response.view?.last?.match(/itemsPerPage=(\d+)/)?.[1] || 30
                        }
                    };
                }
                return Array.isArray(response) ? { data: response, totalItems: response.length } : { data: [], totalItems: 0 };
            },
            providesTags: (result) =>
                result?.data
                    ? [
                        ...result.data.map(({ id }) => ({ type: 'House', id })),
                        { type: 'House', id: 'LIST' }
                    ]
                    : [{ type: 'House', id: 'LIST' }],
            // ✅ Configuration améliorée pour éviter les problèmes de cache
            keepUnusedDataFor: 300, // 5 minutes
            refetchOnMountOrArgChange: true, // ✅ CHANGEMENT: true au lieu de 30 pour forcer le refetch
            refetchOnFocus: true, // ✅ Ajout pour refetch quand l'app revient au premier plan
        }),
        getHouse: builder.query({
            query: (id) => `houses/${id}`,
            transformResponse: (response) => {
                return {...response, user: response.user?.id};
            },
            providesTags: (result, error, id) => [{ type: 'House', id }],
            // ✅ Configuration pour éviter les problèmes de cache
            keepUnusedDataFor: 300, // 5 minutes
            refetchOnMountOrArgChange: true, // Toujours refetch quand le composant se monte
        }),

        // Recherche avancée d'annonces (publique) - SANS AUTH
        searchHouses: builder.query({
            query: (searchParams) => {
                const params = new URLSearchParams();

                if (searchParams.query) params.append('search', searchParams.query);
                if (searchParams.location) params.append('location', searchParams.location);
                if (searchParams.radius) params.append('radius', searchParams.radius);
                if (searchParams.filters) {
                    Object.entries(searchParams.filters).forEach(([key, value]) => {
                        if (value !== null && value !== undefined) {
                            params.append(key, value);
                        }
                    });
                }

                return `houses/search?${params.toString()}`;
            },
            transformResponse: (response) => {
                if (response.member) {
                    return {
                        results: response.member,
                        totalItems: response.totalItems || 0,
                        facets: response.facets || {},
                    };
                }
                return { results: [], totalItems: 0, facets: {} };
            },
            providesTags: [{ type: 'House', id: 'SEARCH' }],
        }),

        // === ENDPOINTS PRIVÉS (AVEC AUTH) ===

        // Récupérer les annonces de l'utilisateur connecté - AVEC AUTH
        getUserHouses: builder.query({
            query: () => 'houses/my-listings',
            transformResponse: (response) => {
                if (response.member) {
                    return response.member;
                }
                return Array.isArray(response) ? response : [];
            },
            providesTags: (result) =>
                result
                    ? [
                        ...result.map(({ id }) => ({ type: 'House', id })),
                        { type: 'UserHouses', id: 'LIST' }
                    ]
                    : [{ type: 'UserHouses', id: 'LIST' }],
        }),

        getUserHousesById: builder.query({
            query: (userId) => {
                const params = new URLSearchParams({
                    'user': userId,
                    'active': true,
                    'itemsPerPage': 50
                });
                return `houses?${params.toString()}`;
            },
            transformResponse: (response) => {
                console.log('getUserHousesById response:', response);
                if (response.member) {
                    return response.member;
                }
                return Array.isArray(response) ? response : [];
            },
            providesTags: (result, error, userId) =>
                result
                    ? [
                        ...result.map(({ id }) => ({ type: 'House', id })),
                        { type: 'UserHouses', id: userId }
                    ]
                    : [{ type: 'UserHouses', id: userId }],
            // ✅ Configuration spéciale pour les profils
            keepUnusedDataFor: 180, // 3 minutes
            refetchOnMountOrArgChange: true,
        }),

        // Créer une nouvelle annonce - AVEC AUTH
        addHouse: builder.mutation({
            query: (newHouse) => {

                // Nettoyer les données avant envoi
                const cleanHouseData = {
                    shortDescription: newHouse.shortDescription,
                    longDescription: newHouse.longDescription || null,
                    price: parseInt(newHouse.price),
                    currency: newHouse.currency,
                    type: newHouse.type,
                    bedrooms: newHouse.bedrooms ? parseInt(newHouse.bedrooms) : null,
                    bathrooms: newHouse.bathrooms ? parseInt(newHouse.bathrooms) : null,
                    garages: newHouse.garages ? parseInt(newHouse.garages) : null,
                    swimmingPools: newHouse.swimmingPools ? parseInt(newHouse.swimmingPools) : null,
                    floors: newHouse.floors ? parseInt(newHouse.floors) : null,
                    surface: newHouse.surface || null,
                    yearOfConstruction: newHouse.yearOfConstruction ? parseInt(newHouse.yearOfConstruction) : null,
                    address: newHouse.address,
                    city: newHouse.city,
                    country: newHouse.country,
                    isForRent: Boolean(newHouse.isForRent),
                    firstImage: newHouse.firstImage,
                    otherImages: newHouse.otherImages || [],
                    location: newHouse.location || { lat: 0, lng: 0 },
                };

                return {
                    url: 'houses',
                    method: 'POST',
                    body: cleanHouseData,
                };
            },
            invalidatesTags: [
                { type: 'House', id: 'LIST' },
                { type: 'UserHouses', id: 'LIST' }
            ],
            transformResponse: (response) => {
                return response;
            },
            transformErrorResponse: (response) => {
                console.error('Error creating house:', response);
                return {
                    status: response.status,
                    data: response.data,
                    message: response.data?.['hydra:description'] || 'Error creating listing'
                };
            },
        }),

        // Mettre à jour une annonce - AVEC AUTH
        updateHouse: builder.mutation({
            query: ({ id, ...updatedFields }) => {
                // Nettoyer les données avant envoi
                const cleanUpdateData = {
                    shortDescription: updatedFields.shortDescription,
                    longDescription: updatedFields.longDescription || null,
                    price: parseInt(updatedFields.price),
                    currency: updatedFields.currency,
                    type: updatedFields.type,
                    bedrooms: updatedFields.bedrooms ? parseInt(updatedFields.bedrooms) : null,
                    bathrooms: updatedFields.bathrooms ? parseInt(updatedFields.bathrooms) : null,
                    garages: updatedFields.garages ? parseInt(updatedFields.garages) : null,
                    swimmingPools: updatedFields.swimmingPools ? parseInt(updatedFields.swimmingPools) : null,
                    floors: updatedFields.floors ? parseInt(updatedFields.floors) : null,
                    surface: updatedFields.surface || null,
                    yearOfConstruction: updatedFields.yearOfConstruction ? parseInt(updatedFields.yearOfConstruction) : null,
                    address: updatedFields.address,
                    city: updatedFields.city,
                    country: updatedFields.country,
                    isForRent: Boolean(updatedFields.isForRent),
                    firstImage: updatedFields.firstImage,
                    otherImages: updatedFields.otherImages || [],
                    location: updatedFields.location || { lat: 0, lng: 0 },
                };

                return {
                    url: `houses/${id}`,
                    method: 'PUT',
                    body: cleanUpdateData,
                };
            },
            invalidatesTags: (result, error, { id }) => [
                { type: 'House', id },
                { type: 'House', id: 'LIST' },
                { type: 'UserHouses', id: 'LIST' }
            ],
            transformResponse: (response) => {
                return response;
            },
            transformErrorResponse: (response) => {
                console.error('Error updating house:', response);
                return {
                    status: response.status,
                    data: response.data,
                    message: response.data?.['hydra:description'] || 'Error updating listing'
                };
            },
        }),

        // Supprimer une annonce - AVEC AUTH
        deleteHouse: builder.mutation({
            query: (id) => ({
                url: `houses/${id}`,
                method: 'DELETE',
            }),
            invalidatesTags: (result, error, id) => [
                { type: 'House', id },
                { type: 'House', id: 'LIST' },
                { type: 'UserHouses', id: 'LIST' }
            ],
            transformErrorResponse: (response) => {
                console.error('Error deleting house:', response);
                return {
                    status: response.status,
                    data: response.data,
                    message: response.data?.['hydra:description'] || 'Error deleting listing'
                };
            },
        }),

        // Changer le statut d'une annonce (actif/inactif) - AVEC AUTH
        toggleHouseStatus: builder.mutation({
            query: ({ id, active }) => ({
                url: `houses/${id}/toggle-status`,
                method: 'PATCH',
                body: { active },
            }),
            invalidatesTags: (result, error, { id }) => [
                { type: 'House', id },
                { type: 'UserHouses', id: 'LIST' }
            ],
        }),

        // Dupliquer une annonce - AVEC AUTH
        duplicateHouse: builder.mutation({
            query: (id) => ({
                url: `houses/${id}/duplicate`,
                method: 'POST',
            }),
            invalidatesTags: [
                { type: 'House', id: 'LIST' },
                { type: 'UserHouses', id: 'LIST' }
            ],
        }),

        // Obtenir les statistiques des annonces de l'utilisateur - AVEC AUTH
        getHouseStats: builder.query({
            query: () => 'houses/stats',
            providesTags: [{ type: 'UserHouses', id: 'STATS' }],
        }),

        // Signaler une annonce - AVEC AUTH
        reportHouse: builder.mutation({
            query: ({ id, reason, description }) => ({
                url: `houses/${id}/report`,
                method: 'POST',
                body: { reason, description },
            }),
        }),

        // Marquer une annonce comme favorite - AVEC AUTH
        toggleFavorite: builder.mutation({
            query: (id) => ({
                url: `houses/${id}/toggle-favorite`,
                method: 'POST',
            }),
            invalidatesTags: (result, error, id) => [
                { type: 'House', id },
            ],
        }),

        // Obtenir les annonces favorites de l'utilisateur - AVEC AUTH
        getFavoriteHouses: builder.query({
            query: () => 'houses/favorites',
            transformResponse: (response) => {
                if (response.member) {
                    return response.member;
                }
                return Array.isArray(response) ? response : [];
            },
            providesTags: [{ type: 'House', id: 'FAVORITES' }],
        }),
    }),
});

export const {
    useGetHousesQuery,
    useGetHouseQuery,
    useGetUserHousesQuery,
    useGetUserHousesByIdQuery,
    useAddHouseMutation,
    useUpdateHouseMutation,
    useDeleteHouseMutation,
    useToggleHouseStatusMutation,
    useDuplicateHouseMutation,
    useGetHouseStatsQuery,
    useSearchHousesQuery,
    useReportHouseMutation,
    useToggleFavoriteMutation,
    useGetFavoriteHousesQuery,
} = houseApi;