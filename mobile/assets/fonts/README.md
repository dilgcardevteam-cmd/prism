Place Montserrat font files here with these filenames:

- Montserrat-Regular.ttf
- Montserrat-SemiBold.ttf
- Montserrat-Bold.ttf

How to get them:
1. Go to Google Fonts: https://fonts.google.com/specimen/Montserrat
2. Select the weights Regular (400), SemiBold (600) and Bold (700) and download.
3. Extract the .ttf files and rename them to match the filenames above if needed.
4. Restart the Expo dev server (or rebuild) so fonts are bundled.

Notes:
- The app loads the fonts in mobile/app/_layout.jsx using `expo-font`.
- Tailwind's default `sans` font is set to `Montserrat` in tailwind.config.js.
- For web, global.css includes @font-face rules referencing these files.
