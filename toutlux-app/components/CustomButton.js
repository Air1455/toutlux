import {StyleSheet} from "react-native";
import {Button, useTheme} from "react-native-paper";
import {FontAwesome5, MaterialCommunityIcons} from "@expo/vector-icons";

export default function CustomButton({ content, variant = 'default', iconName, onPress, radius= 'standard' }) {
    const { colors } = useTheme();
    const isYellow = variant === 'yellow';
    const isBlue = variant === 'blue';
    const backgroundColor= isYellow ? colors.primary : isBlue ? colors.tertiary: 'transparent'
    const labelColor= (isYellow || isBlue) ? 'white' : colors.text
    const borderRadius= radius === 'standard' ? 8 : 9999

    return (
        <Button
            style={[styles.btn, {backgroundColor, borderRadius}]}
            mode={variant === 'default' ? 'outlined' : 'contained'}
            contentStyle={styles.btnContent}
            labelStyle={[styles.btnLabel, {color: labelColor}]}
            onPress={onPress}
            icon={({ size, color }) =>
                iconName ? <FontAwesome5 name={iconName} size={18} color={color} /> : null
            }
        >
            {content}
        </Button>
    );
}


const styles = StyleSheet.create({
    btn: {
        borderRadius: 9999,
        backgroundColor: 'white',
        height: 30,
    },
    btnContent: {
        height: 30,
        justifyContent: 'center',
        marginVertical: 0,
    },
    btnLabel: {
        fontSize: 14,
        lineHeight: 16,
        marginVertical: 0,
    },
});
