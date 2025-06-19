import { Text, View } from 'react-native';
import {SafeScreen} from "@components/layout/SafeScreen";

export default function InboxScreen() {
    return (
        <SafeScreen>
            <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center' }}>
                <Text>Bienvenue sur la page des inboxs</Text>
            </View>
        </SafeScreen>
    );
}
