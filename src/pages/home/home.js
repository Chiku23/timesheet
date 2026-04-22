const shareBtn = document.querySelector('.share-btn');

if (shareBtn) {
    shareBtn.addEventListener('click', async function () {
        
        // Hide the button to exclude it from the screenshot
        shareBtn.style.visibility = 'hidden';
        
        try {
            // Scroll to top for a clean capture
            const scrollPos = window.scrollY;
            window.scrollTo(0, 0);

            // Capture the full page using html2canvas
            const canvas = await html2canvas(document.body, {
                allowTaint: true,
                useCORS: true,
                scale: 1,
                scrollX: 0,
                scrollY: 0,
                windowWidth: window.innerWidth,
                windowHeight: document.documentElement.scrollHeight
            });
            
            // Restore scroll position
            window.scrollTo(0, scrollPos);

            // Convert canvas to base64 image
            const imgData = canvas.toDataURL('image/png');
            
            // Build the pdfmake document definition
            const docDefinition = {
                // Set page size to match the captured screenshot dimensions (in points)
                pageSize: {
                    width: canvas.width,
                    height: canvas.height
                },
                pageMargins: [0, 0, 0, 0],
                content: [
                    {
                        image: imgData,
                        width: canvas.width,
                        height: canvas.height
                    }
                ]
            };
            
            // Generate and download the PDF
            pdfMake.createPdf(docDefinition).download('screenshot.pdf');
            
        } catch (error) {
            console.error('Failed to generate PDF:', error);
            alert('Something went wrong while generating the PDF.');
        } finally {
            // Restore the button
            shareBtn.style.visibility = 'visible';
        }
    });
}