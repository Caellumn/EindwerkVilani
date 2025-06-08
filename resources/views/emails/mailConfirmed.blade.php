<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body style="margin:0; padding:0; background-color:#faf3ee; font-family:Arial, sans-serif;">

<!-- Email Container -->
<div style="max-width:600px; margin:0 auto; background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
  
  <!-- Header -->
  <div style="background-color:#a5673f; background-image:url('https://example.com/flowers-border.png'); background-repeat:repeat-x; padding:20px; text-align:center;">
    <div style="max-width:150px; margin:0 auto;">
      <img src="https://res.cloudinary.com/dqq0m8xb3/image/upload/v1748090686/products/tokg8mxxfp6fs1waav4b.jpg" 
           alt="Kapsalon Vilani - Logo" 
           style="width:100%; height:auto; display:block; border-radius:8px;">
    </div>
  </div>

  <!-- Main Content -->
  <div style="padding:30px 20px; line-height:1.6; color:#5a3d2b;">
    
    <!-- Title -->
    <h1 style="font-size:24px; margin:0 0 20px 0; color:#22c55e; text-align:center;">✅ Uw afspraak is bevestigd!</h1>
    
    <!-- Greeting -->
    <p style="margin:0 0 15px 0;">Beste <strong>{{ $customerName }}</strong>,</p>
    <p style="margin:0 0 20px 0;">Goed nieuws! Uw afspraak bij <strong>Kapsalon Vilani</strong> is bevestigd. Wij kijken ernaar uit u te verwelkomen.</p>
    
    <!-- Booking Details Card -->
    <div style="background-color:#f0fdf4; border-left:4px solid #22c55e; border-radius:6px; padding:20px; margin:20px 0; border:1px solid #bbf7d0;">
      
      <h3 style="margin:0 0 15px 0; color:#22c55e; font-size:18px;">✅ Bevestigde Afspraak</h3>
      
      <!-- Date & Time -->
      <div style="margin:10px 0;">
        <span style="font-weight:bold; color:#5a3d2b;">Datum:</span>
        <span style="color:#5a3d2b; margin-left:10px;">{{ $bookingDate }}</span>
      </div>
      
      <div style="margin:10px 0;">
        <span style="font-weight:bold; color:#5a3d2b;">geschatte duur:</span>
        <span style="color:#5a3d2b; margin-left:10px;">{{ $bookingTime }} - {{ $endTime }}</span>
      </div>
      
      <div style="margin:10px 0;">
        <span style="font-weight:bold; color:#5a3d2b;">Status:</span>
        <span style="color:#22c55e; font-weight:bold; margin-left:10px;">✅ Bevestigd</span>
      </div>
      
      <!-- Services -->
      @if($services && $services->count() > 0)
        <div style="margin:20px 0 10px 0;">
          <span style="font-weight:bold; color:#5a3d2b;">{{ $services->count() === 1 ? 'Behandeling:' : 'Behandelingen:' }}</span>
        </div>
        <div style="margin:0 0 15px 20px;">
          @foreach($services as $service)
            <div style="margin:5px 0; color:#5a3d2b;">
              <span style="color:#22c55e; font-weight:bold; margin-right:8px;">•</span>
              <span>{{ $service->name }}</span>
            </div>
          @endforeach
        </div>
      @endif
      
      <!-- Products -->
      @if($products && $products->count() > 0)
        <div style="margin:20px 0 10px 0;">
          <span style="font-weight:bold; color:#5a3d2b;">{{ $products->count() === 1 ? 'Product:' : 'Producten:' }}</span>
        </div>
        <div style="margin:0 0 15px 20px;">
          @foreach($products as $product)
            <div style="margin:5px 0; color:#5a3d2b;">
              <span style="color:#22c55e; font-weight:bold; margin-right:8px;">•</span>
              <span>{{ $product->name }}</span>
            </div>
          @endforeach
        </div>
      @endif
      
      <!-- Remarks -->
      @if($booking->remarks)
        <div style="margin:20px 0 0 0;">
          <span style="font-weight:bold; color:#5a3d2b;">Opmerkingen:</span>
          <div style="margin:5px 0 0 0; color:#5a3d2b;">{{ $booking->remarks }}</div>
        </div>
      @endif
      
    </div>
    
    <!-- Important Notice -->
    <div style="background-color:#fef3c7; border-radius:6px; padding:15px; margin:20px 0; border:1px solid #fbbf24;">
      <div style="color:#92400e; font-size:14px; line-height:1.5;">
        <strong>📍 Belangrijk:</strong> Kom graag 5-10 minuten voor uw afspraak aan. Het adres is <strong>Puttestraat 3, 2940 Stabroek</strong>.
      </div>
    </div>
    
    <div style="background-color:#f0f9ff; border-radius:6px; padding:15px; margin:20px 0; border:1px solid #7dd3fc;">
      <div style="color:#0369a1; font-size:14px; line-height:1.5;">
        <strong>💡 Wijzigingen nodig?</strong> Neem contact met ons op via <strong>+32 3 294 48 33</strong> of <strong>info@kapsalonvilani.be</strong> voor wijzigingen of annuleringen.
      </div>
    </div>
    
    <!-- Closing -->
    <p style="margin:20px 0 10px 0;">We kijken ernaar uit u te verwelkomen bij Kapsalon Vilani!</p>
    <p style="margin:0;">Met vriendelijke groet,<br><strong>Het team van Kapsalon Vilani</strong></p>
    
  </div>

  <!-- Footer -->
  <div style="background-color:#fff8f3; padding:20px; text-align:center; border-top:1px solid #f0e6d6;">
    <div style="font-size:12px; color:#777777; line-height:1.4;">
      <div style="margin:5px 0;"><strong>Kapsalon Vilani</strong> • Puttestraat 3 • 2940 Stabroek</div>
      <div style="margin:5px 0;">Tel: <span style="color:#a5673f;">+32 3 294 48 33</span> • <a href="mailto:info@kapsalonvilani.be" style="color:#a5673f; text-decoration:none;">info@kapsalonvilani.be</a></div>
      <div style="margin:10px 0;">
        Volg ons op <a href="https://www.facebook.com/kapsalonvilani" style="color:#a5673f; text-decoration:none; font-weight:bold;">Facebook</a>
      </div>
    </div>
  </div>

</div>

</body>
</html>
