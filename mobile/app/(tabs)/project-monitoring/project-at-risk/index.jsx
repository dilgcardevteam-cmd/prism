import {
  View,
  Text
} from 'react-native';
import { APP_COLORS } from '../../../../constants/theme';
import { TYPOGRAPHY_DEFAULTS } from '../../../../constants/typography';

export default function projectAtRisk() {
  return (
    <View style={{ flex: 1, backgroundColor: APP_COLORS.background, padding: 16 }}>
      <Text
        style={{
          color: APP_COLORS.primary,
          fontFamily: TYPOGRAPHY_DEFAULTS.fontFamily.semiBold,
          fontSize: 18,
        }}
      >
        Project at Risk page here, place elements needed for project at risk in this page.
      </Text>
    </View>
  )
}