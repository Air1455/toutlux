import React, { useEffect, useState } from 'react';
import { setSearchQuery } from "@/redux/houseFilterReducer";
import { useDispatch, useSelector } from "react-redux";
import { useTranslation } from 'react-i18next';
import { TextInput } from '@/components/form/TextInput';

const SearchBar = () => {
    const dispatch = useDispatch();
    const { t } = useTranslation();
    const searchQuery = useSelector(state => state.houseFilter.searchQuery);
    const [input, setInput] = useState(searchQuery);

    useEffect(() => {
        const timeout = setTimeout(() => {
            dispatch(setSearchQuery(input));
        }, 300);
        return () => clearTimeout(timeout);
    }, [input, dispatch]);

    useEffect(() => {
        setInput(searchQuery);
    }, [searchQuery]);

    const clearInput = () => {
        setInput('');
        dispatch(setSearchQuery(''));
    };

    return (
        <TextInput
            variant="search"
            placeholder={t('search.placeholder')}
            value={input}
            onChangeText={setInput}
            clearable={true}
            onClear={clearInput}
        />
    );
};

export default SearchBar;