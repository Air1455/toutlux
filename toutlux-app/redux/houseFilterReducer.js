import { createSlice } from '@reduxjs/toolkit';

const initialState = {
    allHouses: [],
    filteredHouses: [],
    searchQuery: '',
    filters: {
        types: ['house', 'hotel'],
        viewMode: 'list',
        priceRange: null,
        // ajoute d'autres filtres ici
    },
};

const houseFilterSlice = createSlice({
    name: 'houseFilter',
    initialState,
    reducers: {
        setAllHouses(state, action) {
            state.allHouses = action.payload;
            state.filteredHouses = action.payload;
        },
        setSearchQuery(state, action) {
            state.searchQuery = action.payload;
            state.filteredHouses = filterHouses(state);
        },
        setFilters(state, action) {
            state.filters = { ...state.filters, ...action.payload };
            state.filteredHouses = filterHouses(state);
        },
        resetFilters(state) {
            state.filters = initialState.filters;
            state.filteredHouses = filterHouses({ ...state, filters: initialState.filters });
        },
    },
});

function normalizeString(str) {
    return str
        .normalize('NFD')               // décompose les caractères accentués
        .replace(/[\u0300-\u036f]/g, '') // supprime les diacritiques
        .toLowerCase();
}

function filterHouses(state) {
    const { allHouses, searchQuery, filters } = state;
    const query = searchQuery.toLowerCase();

    return allHouses.filter(house => {
        // Sécuriser les champs texte
        const city = normalizeString(house.city || '');
        const country = normalizeString(house.country || '');
        const address = normalizeString(house.address || '');

        const matchesSearch = query === '' ? true : (city.includes(query) || country.includes(query) || address.includes(query));

        const houseType = house.type ? house.type.toLowerCase() : '';

        let matchesType = false;
        const typesSelected = filters.types;

        if (typesSelected.length === 0) {
            matchesType = false; // ou false si tu veux cacher tout quand rien sélectionné
        } else if (typesSelected.includes('house') && !typesSelected.includes('hotel')) {
            matchesType = houseType !== 'hotel';
        } else if (!typesSelected.includes('house') && typesSelected.includes('hotel')) {
            matchesType = houseType === 'hotel';
        } else if (typesSelected.includes('house') && typesSelected.includes('hotel')) {
            matchesType = true;
        } else {
            matchesType = typesSelected.includes(houseType);
        }

        // const matchesPrice = filters.priceRange
        //     ? house.price >= filters.priceRange[0] && house.price <= filters.priceRange[1]
        //     : true;

        return matchesSearch && matchesType;
    });
}


export const { setAllHouses, setSearchQuery, setFilters, resetFilters } = houseFilterSlice.actions;
export default houseFilterSlice.reducer;
