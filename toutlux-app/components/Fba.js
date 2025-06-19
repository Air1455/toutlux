import React from 'react';
import { StyleSheet } from 'react-native';
import {Button, useTheme} from 'react-native-paper';
import Svg, { Path } from 'react-native-svg';
import {useRouter} from "expo-router";
import {useTranslation} from "react-i18next";

export default function Fba() {
    const {colors}= useTheme()
    const router = useRouter();
    const {t}= useTranslation()

    return (
        <Button
            mode="contained"
            onPress={() => router.replace('(tabs)/home')}
            contentStyle={styles.btnContent}
            style={[styles.btn, {backgroundColor: colors.primary}]}
            labelStyle={styles.label}
            icon={() => (
                <Svg width={21} height={21} viewBox="0 0 24 24">
                    <Path fill="none" d="M0 0h24v24H0z" />
                    <Path
                        d="M15 5l-1.41 1.41L18.17 11H2v2h16.17l-4.59 4.59L15 19l7-7-7-7z"
                        fill="#000"
                    />
                </Svg>
            )}
        >
            {t('start')}
        </Button>
    );
}

const styles = StyleSheet.create({
    btn: {
        // position: 'absolute',
        // bottom: 40,
        // right: 40,
        borderRadius: 8,
    },
    btnContent: {
        flexDirection: 'row-reverse',
        height: 45,
        paddingHorizontal: 8,
        alignItems: 'center',
    },
    label: {
        fontSize: 14,
        lineHeight: 16,
        fontFamily: 'Roboto',
        color: '#000',
    },
});
