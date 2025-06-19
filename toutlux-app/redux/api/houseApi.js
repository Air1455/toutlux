import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const houseApi = createApi({
    reducerPath: 'houseApi',
    baseQuery: fetchBaseQuery({
        baseUrl: `${process.env.EXPO_PUBLIC_API_URL}/api/`,
        prepareHeaders: (headers) => {
            headers.set('Content-Type', 'application/ld+json'); // pour API Platform
            return headers;
        }
    }),
    tagTypes: ['House'],
    endpoints: (builder) => ({
        getHouses: builder.query({
            query: () => 'houses',
            transformResponse: (response) => {
                return response['member'] || [];
            },
            providesTags: (result) =>
                result
                    ? [...result.map(({ id }) => ({ type: 'House', id })), { type: 'House', id: 'LIST' }]
                    : [{ type: 'House', id: 'LIST' }],
        }),

        // POST /api/houses
        addHouse: builder.mutation({
            query: (newHouse) => ({
                url: 'houses',
                method: 'POST',
                body: newHouse,
            }),
            invalidatesTags: [{ type: 'House', id: 'LIST' }],
        }),

        // PUT /api/houses/1
        updateHouse: builder.mutation({
            query: ({ id, ...updatedFields }) => ({
                url: `houses/${id}`,
                method: 'PUT',
                body: updatedFields,
            }),
            invalidatesTags: (result, error, { id }) => [{ type: 'House', id }],
        }),

        // DELETE /api/houses/1
        deleteHouse: builder.mutation({
            query: (id) => ({
                url: `houses/${id}`,
                method: 'DELETE',
            }),
            invalidatesTags: (result, error, id) => [{ type: 'House', id }],
        }),
    }),
});

export const {
    useGetHousesQuery,
    useAddHouseMutation,
    useUpdateHouseMutation,
    useDeleteHouseMutation,
} = houseApi;
