// IPS QR kod za dopunu kartice: tekst priprema server (data-tekst), ovde se samo iscrtava
(function () {
  const mesto = document.getElementById("ipsQr");
  if (window.QRCode && mesto) {
    new QRCode(mesto, {
      text: mesto.dataset.tekst,
      width: 220,
      height: 220,
      correctLevel: QRCode.CorrectLevel.M,
    });
  }
})();
