export const getPromptFont = (weight) => {
    const fontMap = {
        'light': 'Prompt_400Regular',     // Fallback
        'normal': 'Prompt_400Regular',    // ✅ Chargée
        'medium': 'Prompt_400Regular',    // Fallback vers Regular
        'semibold': 'Prompt_400Regular',  // Fallback vers Regular
        'bold': 'Prompt_800ExtraBold',    // ✅ Chargée
        'extrabold': 'Prompt_800ExtraBold' // ✅ Chargée
    };

    return fontMap[weight] || 'Prompt_400Regular';
};

// Utilisation dans vos styles :
const styles = StyleSheet.create({
    label: {
        fontSize: 16,
        fontWeight: '600',
        fontFamily: getPromptFont('semibold'), // Utilisera Prompt_400Regular
    },
    value: {
        fontFamily: getPromptFont('normal'),   // Utilisera Prompt_400Regular
    },
    title: {
        fontFamily: getPromptFont('bold'),     // Utilisera Prompt_800ExtraBold
    }
});