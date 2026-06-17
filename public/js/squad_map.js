async function exportSquadMapPdf() {
    const { jsPDF } = window.jspdf;
    const element = document.getElementById('squad-map-capture');
    
    const canvas = await html2canvas(element, {
        backgroundColor: "#f5efe1",
        scale: 2
    });
    
    const img = canvas.toDataURL("image/png");
    const pdf = new jsPDF({ orientation: "landscape", unit: "pt", format: "a3" });
    const pageW = pdf.internal.pageSize.getWidth();
    const pageH = pdf.internal.pageSize.getHeight();
    const ratio = Math.min(pageW / canvas.width, pageH / canvas.height);
    const w = canvas.width * ratio;
    const h = canvas.height * ratio;
    pdf.addImage(img, "PNG", (pageW - w) / 2, (pageH - h) / 2, w, h);
    pdf.save("squad-map.pdf");
}
