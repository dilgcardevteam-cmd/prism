import {
  View,
  Text
} from 'react-native';
import { APP_COLORS } from '../../../../constants/theme';
import { TYPOGRAPHY_DEFAULTS } from '../../../../constants/typography';

export default function systemMaintenance() {
  return (
    <View style={{ flex: 1, backgroundColor: APP_COLORS.background, padding: 16 }}>
      <Text
        style={{
          color: APP_COLORS.primary,
          fontFamily: TYPOGRAPHY_DEFAULTS.fontFamily.semiBold,
          fontSize: 18,
        }}
      >
        System Maintenance page here, place elements needed for system maintenance in this page.
      </Text>
    </View>
  )
}
