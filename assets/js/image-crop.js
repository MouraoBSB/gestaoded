/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 18:02:00
 */

let cropper = null;
let currentFile = null;

function initImageCrop(inputId, previewId, cropWidth, cropHeight) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    const modal = document.getElementById('cropModal');
    const cropImage = document.getElementById('cropImage');
    const cropBtn = document.getElementById('cropBtn');
    const cancelCropBtn = document.getElementById('cancelCropBtn');
    
    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        if (!file.type.match('image.*')) {
            alert('Por favor, selecione uma imagem válida.');
            return;
        }
        
        currentFile = file;
        const reader = new FileReader();
        
        reader.onload = function(event) {
            cropImage.src = event.target.result;
            modal.classList.remove('hidden');
            
            if (cropper) {
                cropper.destroy();
            }
            
            cropper = new Cropper(cropImage, {
                aspectRatio: cropWidth / cropHeight,
                viewMode: 1,
                minCropBoxWidth: cropWidth,
                minCropBoxHeight: cropHeight,
                autoCropArea: 1,
                responsive: true,
                guides: true,
                center: true,
                highlight: true,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
            });
        };
        
        reader.readAsDataURL(file);
    });
    
    cropBtn.addEventListener('click', function() {
        if (!cropper) return;
        
        const canvas = cropper.getCroppedCanvas({
            width: cropWidth,
            height: cropHeight,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });
        
        canvas.toBlob(function(blob) {
            const croppedFile = new File([blob], currentFile.name, {
                type: currentFile.type,
                lastModified: Date.now(),
            });
            
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(croppedFile);
            input.files = dataTransfer.files;
            
            const previewUrl = URL.createObjectURL(blob);
            preview.src = previewUrl;
            preview.classList.remove('hidden');
            
            modal.classList.add('hidden');
            cropper.destroy();
            cropper = null;
        }, currentFile.type);
    });
    
    cancelCropBtn.addEventListener('click', function() {
        modal.classList.add('hidden');
        input.value = '';
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    });
}
