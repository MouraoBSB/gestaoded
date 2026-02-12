<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 18:02:00
 */
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<div id="cropModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-auto">
        <div class="p-6">
            <h3 class="text-2xl font-bold mb-4">Recortar Imagem</h3>
            <div class="mb-4">
                <img id="cropImage" class="max-w-full" style="max-height: 60vh;">
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" id="cancelCropBtn" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition font-semibold">
                    Cancelar
                </button>
                <button type="button" id="cropBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition font-semibold">
                    Aplicar Recorte
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/image-crop.js"></script>
