{{--
  Font Poppins untuk semua PDF, sama dengan font UI (next/font Poppins).
  dompdf hanya mengenal varian normal / bold / italic:
  - normal → Poppins Regular
  - bold (termasuk font-weight 600) → Poppins SemiBold, setara `font-semibold` di UI
  File TTF ada di resources/fonts/poppins (lisensi OFL, lihat OFL.txt di folder yang sama).
--}}
<style>
  @font-face {
    font-family: 'Poppins';
    font-style: normal;
    font-weight: normal;
    src: url('{{ resource_path('fonts/poppins/Poppins-Regular.ttf') }}') format('truetype');
  }
  @font-face {
    font-family: 'Poppins';
    font-style: normal;
    font-weight: bold;
    src: url('{{ resource_path('fonts/poppins/Poppins-SemiBold.ttf') }}') format('truetype');
  }
  @font-face {
    font-family: 'Poppins';
    font-style: italic;
    font-weight: normal;
    src: url('{{ resource_path('fonts/poppins/Poppins-Italic.ttf') }}') format('truetype');
  }
</style>
