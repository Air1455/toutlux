import React, { useEffect, useState } from 'react';
import { StyleSheet } from 'react-native';
import { TextInput, useTheme } from 'react-native-paper';
import { setSearchQuery } from "@/redux/houseFilterReducer";
import { useDispatch, useSelector } from "react-redux";
import { useTranslation } from 'react-i18next';

const SearchBar = () => {
    const { colors } = useTheme();
    const dispatch = useDispatch();
    const { t } = useTranslation();
    const searchQuery = useSelector(state => state.houseFilter.searchQuery);
    const [input, setInput] = useState(searchQuery);

    useEffect(() => {
        const timeout = setTimeout(() => {
            dispatch(setSearchQuery(input));
        }, 300);
        return () => clearTimeout(timeout);
    }, [input]);

    useEffect(() => {
        setInput(searchQuery);
    }, [searchQuery]);

    const clearInput = () => {
        setInput('');
        dispatch(setSearchQuery(''));
    };

    return (
        <TextInput
            mode="outlined"
            placeholder={t('search.placeholder')}
            placeholderTextColor={colors.placeholder}
            value={input}
            onChangeText={setInput}
            style={styles.input}
            theme={{ roundness: 8 }}
            textColor={"black"}
            right={
                input.length > 0 ? (
                    <TextInput.Icon
                        icon="close-circle"
                        color={colors.placeholder}
                        onPress={clearInput}
                    />
                ) : (
                    <TextInput.Icon icon="magnify" color={colors.placeholder} />
                )
            }
        />
    );
};

const styles = StyleSheet.create({
    input: {
        width: '100%',
        height: 56,
        backgroundColor: '#ffffff',
        fontSize: 14,
        borderRadius: 8,
        justifyContent: 'center',
    }
});

export default SearchBar;
