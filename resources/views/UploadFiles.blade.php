@extends('layouts.app')

@section('title', 'Almacenamiento en la Nube')

@section('styles')
<style>
    @media (max-width: 640px) {
        .main-container {
            padding-top: 0;
        }
    }
    
    /* Ocultar el encabezado de la página */
    .app-page-title, .page-title-wrapper, .page-title-heading {
        display: none !important;
    }
    
    /* Estilos mejorados para archivos */
    .file-card {
        transition: all 0.25s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }
    
    .file-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        z-index: 10;
    }
    
    .file-card:active {
        transform: translateY(0) scale(0.98);
    }
    
    /* Efecto visual al hacer doble clic */
    .file-card.dblclick-effect {
        animation: dblclick-animation 0.3s ease;
    }
    
    /* Animación para el icono */
    .file-card:hover .fa-folder,
    .file-card:hover .fa-file-excel {
        animation: bounce 0.5s ease;
    }
    
    /* Vista previa de Excel con líneas alternadas */
    .excel-preview {
        background: repeating-linear-gradient(
            rgba(16, 185, 129, 0.05) 0px,
            rgba(16, 185, 129, 0.05) 24px,
            rgba(255, 255, 255, 0.5) 24px,
            rgba(255, 255, 255, 0.5) 48px
        );
    }
    
    /* Indicador de doble clic */
    .dblclick-hint {
        position: absolute;
        top: 0;
        right: 0;
        background-color: rgba(0,0,0,0.5);
        color: white;
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 0 6px 0 6px;
        opacity: 0;
        transition: opacity 0.3s;
    }
    
    .file-card:hover .dblclick-hint {
        opacity: 1;
    }
    
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }
    
    @keyframes dblclick-animation {
        0% { transform: scale(1); background-color: #ffffff; }
        50% { transform: scale(0.95); background-color: #f0f9ff; }
        100% { transform: scale(1); background-color: #ffffff; }
    }
    
    /* Estilos mejorados para pestañas */
    .tabs-container {
        border-bottom: 2px solid #f3f4f6;
        margin-bottom: 1.5rem;
    }
    
    .tab-button {
        position: relative;
        padding: 0.75rem 1.25rem;
        margin-right: 0.5rem;
        font-weight: 500;
        color: #6b7280;
        border-radius: 8px 8px 0 0;
        transition: all 0.2s ease;
    }
    
    .tab-button:hover {
        color: #1f7f95;
        background-color: #f9fafb;
    }
    
    .tab-active {
        color: #1f7f95;
        font-weight: 600;
    }
    
    .tab-active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: #1f7f95;
        border-radius: 2px 2px 0 0;
    }
    
    /* Mejora para breadcrumb */
    .breadcrumb-container {
        background-color: #f9fafb;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        margin-bottom: 1.5rem;
    }
    
    .breadcrumb-item {
        display: inline-flex;
        align-items: center;
        color: #6b7280;
        transition: all 0.2s;
    }
    
    .breadcrumb-item:hover {
        color: #1f7f95;
    }
    
    .breadcrumb-separator {
        margin: 0 0.5rem;
        color: #d1d5db;
    }
    
    /* Estilos para el área de drag & drop */
    .dropzone {
        border: 2px dashed #d1d5db;
        border-radius: 10px;
        background-color: #f9fafb;
        padding: 2.5rem 2rem;
        transition: all 0.2s ease;
        text-align: center;
    }
    
    .dropzone:hover, .dropzone.drag-active {
        border-color: #1f7f95;
        background-color: rgba(31, 127, 149, 0.05);
    }
    
    .dropzone-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: #1f7f95;
        transition: transform 0.3s ease;
    }
    
    .dropzone:hover .dropzone-icon {
        transform: translateY(-5px);
    }
    
    /* Mejoras para mensajes de feedback */
    .feedback-message {
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1.5rem;
        border-left-width: 4px;
        display: flex;
        align-items: center;
    }
    
    .feedback-success {
        background-color: #ecfdf5;
        border-left-color: #10b981;
        color: #065f46;
    }
    
    .feedback-error {
        background-color: #fef2f2;
        border-left-color: #ef4444;
        color: #991b1b;
    }
    
    /* Animación de carga */
    .loading-spinner {
        border: 3px solid rgba(31, 127, 149, 0.1);
        border-radius: 50%;
        border-top: 3px solid #1f7f95;
        width: 30px;
        height: 30px;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Estilos para búsqueda y filtros */
    .search-container {
        background-color: #f9fafb;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .search-input {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 0.5rem 1rem 0.5rem 2.5rem;
        width: 100%;
        transition: all 0.2s;
    }
    
    .search-input:focus {
        border-color: #1f7f95;
        box-shadow: 0 0 0 2px rgba(31, 127, 149, 0.1);
        outline: none;
    }
    
    /* Estilos para vista de lista */
    .list-view .file-item {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f3f4f6;
        transition: all 0.2s;
    }
    
    .list-view .file-item:hover {
        background-color: #f9fafb;
    }
    
    /* Vista responsive mejorada */
    @media (max-width: 768px) {
        .grid-view {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        
        .tabs-container {
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 0.5rem;
        }
        
        .tab-button {
            display: inline-block;
            padding: 0.75rem 1rem;
        }
        
        .action-buttons {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .action-buttons .btn {
            width: 100%;
            justify-content: center;
        }
    }
    
    @media (max-width: 640px) {
        .grid-view {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        
        .file-actions {
            flex-direction: column;
        }
        
        .breadcrumb-container {
            overflow-x: auto;
            white-space: nowrap;
            padding: 0.5rem;
        }
        
        .dropzone {
            padding: 1.5rem 1rem;
        }
        
        .dropzone-icon {
            font-size: 2.5rem;
        }
    }

    /* Modal mejorado */
    .modal {
        display: none;
        position: fixed;
        z-index: 100;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
        animation: fadeIn 0.3s;
    }
    
    .modal-content {
        background-color: #fff;
        margin: 5% auto;
        padding: 2rem;
        border-radius: 12px;
        width: 90%;
        max-width: 700px;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        animation: slideIn 0.3s;
        position: relative;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideIn {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    /* Mejora para botonery y controles */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-weight: 500;
        border-radius: 8px;
        padding: 0.625rem 1.25rem;
        transition: all 0.2s ease;
    }
    
    .btn-primary {
        background-color: #1f7f95;
        color: white;
    }
    
    .btn-primary:hover {
        background-color: #176b7f;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .btn-secondary {
        background-color: #f3f4f6;
        color: #4b5563;
    }
    
    .btn-secondary:hover {
        background-color: #e5e7eb;
        transform: translateY(-1px);
    }
    
    .btn-success {
        background-color: #10b981;
        color: white;
    }
    
    .btn-success:hover {
        background-color: #059669;
    }
    
    /* Botones personalizados con el color corporativo */
.btn-geiico {
    background-color: #1f7f95;
}

.btn-geiico:hover {
    background-color: #176b7f;
}
    
    /* Inputs mejorados */
    .form-input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        transition: all 0.2s;
    }
    
    .form-input:focus {
        border-color: #1f7f95;
        box-shadow: 0 0 0 2px rgba(31, 127, 149, 0.1);
        outline: none;
    }
    
    /* Indicador de progreso para la subida */
    .progress-bar {
        height: 8px;
        background-color: #e5e7eb;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 1rem;
    }
    
    .progress-fill {
        height: 100%;
        background-color: #1f7f95;
        border-radius: 4px;
        transition: width 0.3s ease;
    }
    
    /* Mejora para carpetas seleccionables */
    .folder-item {
        border-radius: 8px;
        margin-bottom: 0.5rem;
        transition: all 0.2s;
        position: relative;
    }
    
    .folder-item:hover {
        background-color: #f9fafb;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .folder-item.selected {
        background-color: #e0f2fe;
        border-left: 3px solid #1f7f95;
    }
    
    /* Dropdown para acciones de archivos */
    .file-dropdown {
        position: relative;
    }
    
    .file-dropdown-menu {
        position: absolute;
        right: 0;
        top: 100%;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        width: 200px;
        z-index: 10;
        overflow: hidden;
        display: none;
    }
    
    .file-dropdown-item {
        padding: 0.75rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #4b5563;
        transition: all 0.2s;
    }
    
    .file-dropdown-item:hover {
        background-color: #f9fafb;
        color: #1f7f95;
    }
    
    /* Animación para elementos cargando */
    .skeleton-loading {
        background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: 8px;
    }
    
    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }

    /* Estilos para interacción con carpetas en modal */
    .folder-item {
        position: relative;
        transition: all 0.2s ease;
    }
    
    .folder-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    /* Efecto de pulsación al hacer doble clic */
    .folder-item:active {
        transform: scale(0.98);
    }
    
    /* Animación suave para el icono */
    .folder-item:hover .fa-folder {
        animation: folder-bounce 0.5s ease;
    }
    
    @keyframes folder-bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-3px); }
    }
    
    /* Tooltip de ayuda para navegación */
    .folder-navigation-tip {
        position: absolute;
        bottom: -20px;
        left: 50%;
        transform: translateX(-50%);
        background-color: rgba(0,0,0,0.7);
        color: white;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 10px;
        opacity: 0;
        transition: opacity 0.2s;
        pointer-events: none;
        z-index: 10;
    }
    
    .folder-item:hover .folder-navigation-tip {
        opacity: 1;
    }

    /* Colores personalizados para iconos de archivos */
.file-pdf {
    color: #e53e3e; /* Rojo para PDF */
}

.file-excel {
    color: #2f855a; /* Verde para Excel */
}

.file-word {
    color: #2b6cb0; /* Azul para Word */
}

.file-ppt {
    color: #c05621; /* Naranja para PowerPoint */
}

.file-image {
    color: #6b46c1; /* Púrpura para imágenes */
}

.file-archive {
    color: #805ad5; /* Violeta para archivos ZIP/RAR */
}

.file-icon {
    color: #4a5568; /* Gris para archivos genéricos */
}

/* Color para icono de carpetas */
.folder-icon {
    color: #ecc94b; /* Amarillo para carpetas */
}

/* Color personalizado para el fondo de los iconos */
.file-card .w-12.bg-emerald-50 {
    background-color: rgba(47, 133, 90, 0.1); /* Fondo verde muy claro para Excel */
}

.file-card .w-12.bg-blue-50 {
    background-color: rgba(66, 153, 225, 0.1); /* Fondo azul muy claro para otros archivos */
}

.file-card .w-12.bg-yellow-50 {
    background-color: rgba(236, 201, 75, 0.1); /* Fondo amarillo muy claro para carpetas */
}
</style>
@endsection

@section('content')
<!-- Modal para seleccionar carpeta -->
<div id="folder-select-modal" class="modal">
    <div class="modal-content">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800">Seleccionar carpeta destino</h2>
            <button id="close-folder-modal" class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-100 transition-all">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="modal-breadcrumb" class="breadcrumb-container flex items-center text-sm text-gray-600 mb-4 flex-wrap">
            <span class="cursor-pointer hover:text-blue-500 mb-1 breadcrumb-item" data-folder-id="root">
                <i class="fas fa-home mr-1"></i>Raíz
            </span>
            <!-- Breadcrumb será generado dinámicamente -->
        </div>
        
        <div class="search-container mb-4">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" class="search-input" placeholder="Buscar carpetas..." id="modal-folder-search">
            </div>
        </div>
        
        <div id="modal-folders-container" class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 max-h-[40vh] overflow-y-auto p-1">
            <!-- Carpetas serán mostradas aquí -->
            <div class="text-center text-gray-500 py-10 col-span-full">
                <div class="flex justify-center mb-3">
                    <div class="loading-spinner"></div>
                </div>
                <p>Cargando carpetas...</p>
            </div>
        </div>
        
        <div class="mt-6 flex justify-between">
            <button id="create-folder-button" class="btn btn-success flex items-center">
                <i class="fas fa-folder-plus"></i>
                <span>Nueva Carpeta</span>
            </button>
            
            <div class="flex gap-3">
                <button id="cancel-folder-select" class="btn btn-secondary">
                    Cancelar
                </button>
                <button id="confirm-folder-select" class="btn btn-primary">
                    Seleccionar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Agregar esta alerta en la parte superior del contenido -->
<div id="permission-alert" class="hidden mb-4 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-info-circle text-blue-500"></i>
        </div>
        <div class="ml-3">
            <p class="text-sm text-blue-700">
                Todos los archivos y carpetas son privados. Solo tú puedes verlos y gestionarlos.
            </p>
        </div>
    </div>
</div>

<!-- Contenido principal -->
<div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
    <!-- Pestañas mejoradas -->
    <div class="tabs-container flex border-b border-gray-200 px-6 pt-4">
        <button id="tab-files" class="tab-button tab-active flex items-center gap-2">
            <i class="fas fa-file-alt"></i>
            <span>Mis Archivos</span>
        </button>
        <button id="tab-upload" class="tab-button flex items-center gap-2">
            <i class="fas fa-upload"></i>
            <span>Subir</span>
        </button>
        <button id="tab-folder" class="tab-button flex items-center gap-2">
            <i class="fas fa-folder-plus"></i>
            <span>Nueva Carpeta</span>
        </button>
        <!-- Pestaña papelera -->
        <button id="tab-trash" class="tab-button flex items-center gap-2">
            <i class="fas fa-trash-alt"></i>
            <span>Papelera</span>
        </button>
    </div>
    
    <div class="p-6">
        <!-- Sección de archivos -->
        <div id="files-section">
            <!-- Añadir estos elementos al section files-section -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 id="section-title" class="text-xl font-semibold text-gray-800">Mis archivos personales</h2>
                    <p class="text-sm text-gray-500 mt-1">Estos archivos son privados y solo visibles para ti</p>
                </div>
                <div class="flex items-center gap-2">
                    <button id="refresh-files" class="btn btn-secondary flex items-center gap-2" title="Actualizar">
                        <i class="fas fa-sync-alt"></i>
                        <span class="hidden sm:inline">Actualizar</span>
                    </button>
                </div>
            </div>
            
            <div id="breadcrumb-container" class="breadcrumb-container flex items-center text-sm text-gray-600 mb-4 flex-wrap">
                <div class="breadcrumb-item cursor-pointer">
                    <i class="fas fa-home"></i>
                    <span>Raíz</span>
                </div>
            </div>
            
            <!-- Add this under the breadcrumb-container in the files-section -->
<div class="search-container mb-4">
    <div class="relative">
        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
        <input type="text" id="file-search" class="search-input" placeholder="Buscar archivos...">
    </div>
</div>
            
            <!-- Contenedor de archivos con vista ajustable -->
            <div id="files-container" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 grid-view">
                <!-- Aquí se cargarán los archivos -->
                <div class="text-center py-10 col-span-full">
                    <div class="flex justify-center mb-3">
                        <div class="loading-spinner"></div>
                    </div>
                    <p class="text-gray-500">Cargando archivos...</p>
                </div>
            </div>
        </div>
        
        <!-- Sección de subida de archivos -->
        <div id="upload-section" class="hidden">
            <div class="max-w-xl mx-auto">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Los archivos que subas serán privados y solo visibles para ti.
                            </p>
                        </div>
                    </div>
                </div>
                
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="mb-6">
                        <label for="fileInput" class="dropzone cursor-pointer block">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-cloud-upload-alt dropzone-icon"></i>
                                <h3 class="font-medium text-gray-800 mb-2 text-lg">Arrastra y suelta tus archivos aquí</h3>
                                <p class="text-gray-500 mb-4">o haz clic para seleccionarlos</p>
                                <button type="button" class="btn btn-primary select-file-btn">
                                    <i class="fas fa-file-upload"></i>
                                    <span>Seleccionar archivos</span>
                                </button>
                                <input type="file" id="fileInput" name="file" class="hidden" />
                            </div>
                        </label>
                        <div id="file-preview" class="mt-4 hidden">
                            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <div class="flex items-center">
                                    <i class="fas fa-file-alt text-gray-500 text-xl mr-3"></i>
                                    <div class="flex-grow">
                                        <p id="file-selected" class="font-medium text-gray-700 truncate"></p>
                                        <p id="file-size" class="text-xs text-gray-500"></p>
                                    </div>
                                    <button type="button" id="remove-file" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="folderSelect" class="block text-gray-700 text-sm font-bold mb-2">
                            <i class="fas fa-folder mr-1 text-yellow-500"></i> 
                            Carpeta destino:
                        </label>
                        <input type="hidden" id="selected-folder-id" name="parent_id" value="" />
                        
                        <div class="flex items-center">
                            <div id="selected-folder-display" class="flex-grow p-3 border border-gray-300 rounded-l-lg bg-gray-50 min-h-[50px] flex items-center">
                                <p class="text-gray-500">Ninguna carpeta seleccionada</p>
                            </div>
                            <button type="button" id="select-folder-btn" class="bg-blue-500 hover:bg-blue-600 text-white h-[50px] px-4 rounded-r-lg transition-all">
                                <i class="fas fa-folder-open"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Progreso de subida -->
                    <div id="upload-progress" class="mb-6 hidden">
                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                            <span>Subiendo archivo...</span>
                            <span id="upload-percentage">0%</span>
                        </div>
                        <div class="progress-bar">
                            <div id="progress-fill" class="progress-fill" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <button type="submit" id="uploadBtn" class="btn btn-primary w-full" disabled>
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Subir a Google Drive</span>
                    </button>
                </form>
                
                <div id="feedback" class="feedback-message hidden mt-6"></div>
                
                <!-- Consejos de uso -->
                <div class="mt-8 bg-blue-50 p-4 rounded-lg border border-blue-100">
                    <h4 class="font-medium text-blue-800 mb-2 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i>
                        Consejos de uso
                    </h4>
                    <ul class="text-sm text-blue-700 space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle mt-1 mr-2"></i>
                            <span>Puedes arrastrar y soltar múltiples archivos al área designada.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle mt-1 mr-2"></i>
                            <span>Selecciona una carpeta de destino antes de subir archivos.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle mt-1 mr-2"></i>
                            <span>Los archivos se abren automáticamente en Google Drive después de subirse.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Sección de creación de carpetas -->
        <div id="folder-section" class="hidden max-w-lg mx-auto">
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Crear Nueva Carpeta</h2>
                <p class="text-gray-500">Crea una nueva carpeta para organizar tus archivos</p>
            </div>
            
            <form id="folderForm" class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex justify-center mb-6">
                    <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-folder-plus text-yellow-500 text-3xl"></i>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="folderName" class="block text-gray-700 text-sm font-bold mb-2">Nombre de la carpeta:</label>
                    <input 
                        type="text" 
                        id="folderName" 
                        name="name" 
                        class="form-input" 
                        placeholder="Mi nueva carpeta" 
                        required
                    />
                    <p class="text-xs text-gray-500 mt-1">Elige un nombre descriptivo para tu carpeta</p>
                </div>
                
                <!-- Selector de ubicación para la carpeta -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Ubicación:</label>
                    
                    <div class="mb-3">
                        <div class="flex items-center mb-2">
                            <input type="radio" id="useCurrentLocation" name="locationOption" value="current" checked 
                                   class="form-radio h-4 w-4 text-blue-500 border-gray-300" />
                            <label for="useCurrentLocation" class="ml-2 text-gray-700">
                                Usar ubicación actual
                            </label>
                        </div>
                        <div class="ml-6 p-3 bg-gray-50 rounded-lg border border-gray-200 flex items-center">
                            <i class="fas fa-folder text-yellow-500 mr-2"></i>
                            <span id="current-location">Raíz</span>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex items-center mb-2">
                            <input type="radio" id="selectDifferentLocation" name="locationOption" value="different" 
                                   class="form-radio h-4 w-4 text-blue-500 border-gray-300" />
                            <label for="selectDifferentLocation" class="ml-2 text-gray-700">
                                Seleccionar otra ubicación
                            </label>
                        </div>
                        
                        <div id="folder-location-selector" class="ml-6 opacity-50 pointer-events-none transition-all">
                            <input type="hidden" id="folder-selected-folder-id" name="selected_folder_id" value="" />
                            <div class="flex items-center">
                                <div id="folder-selected-folder-display" class="flex-grow p-3 border border-gray-300 rounded-l-lg bg-gray-50 min-h-[50px] flex items-center">
                                    <p class="text-gray-500">Ninguna carpeta seleccionada</p>
                                </div>
                                <button type="button" id="folder-select-location-btn" class="bg-blue-500 hover:bg-blue-600 text-white h-[50px] px-4 rounded-r-lg transition-all">
                                    <i class="fas fa-folder-open"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" id="createFolderBtn" class="btn btn-primary w-full">
                    <i class="fas fa-folder-plus"></i>
                    <span>Crear Carpeta</span>
                </button>
            </form>
            
            <div id="folder-feedback" class="feedback-message hidden mt-6"></div>
        </div>
        
        <!-- Sección de papelera -->
        <div id="trash-section" class="hidden">
            <!-- Añadir esto en el trash-section -->
            <div class="flex justify-between items-start sm:items-center gap-4 mb-6 flex-col sm:flex-row">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Papelera</h2>
                    <p class="text-sm text-gray-500 mt-1">Archivos eliminados temporalmente</p>
                </div>
                
                <div class="flex gap-2">
                    <button id="empty-trash" class="btn btn-secondary flex items-center gap-2">
                        <i class="fas fa-trash-alt"></i>
                        <span>Vaciar papelera</span>
                    </button>
                </div>
            </div>
            
            <!-- Contenedor para archivos en papelera -->
            <div id="trash-container" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <div class="text-center text-gray-500 py-10 col-span-full">
                    <div class="flex justify-center mb-3">
                        <div class="loading-spinner"></div>
                    </div>
                    <p>Cargando papelera...</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="{{ asset('js/Drag.js') }}"></script>
<script>
// Declarar variables en el ámbito global
let currentFolderId = null;
let breadcrumbPath = [];
let modalCurrentFolderId = null;
let modalBreadcrumbPath = [];
let selectedFolderId = null;
let selectedFolderName = null;

document.addEventListener('DOMContentLoaded', function() {
    // Aseguramos que el evento usuarioAutenticado se dispare cuando auth.js determine que el usuario está autenticado
    // Este es un backup por si el evento original no se dispara
    setTimeout(function() {
        // Verificar si ya se ejecutó el código principal
        if (typeof window.navigateToFolder === 'undefined') {
            // Si auth.js no disparó el evento, lo disparamos manualmente
            const authToken = localStorage.getItem('auth_token');
            if (authToken) {
                console.log('Iniciando manualmente la interfaz de UploadFiles');
                document.dispatchEvent(new CustomEvent('usuarioAutenticado', { 
                    detail: { name: localStorage.getItem('user_name') }
                }));
            }
        }
    }, 1500);
    
    // Código comentado temporalmente hasta que se implementen los botones
    /*
    // Toggle entre vista de cuadrícula y lista
    document.getElementById('toggle-view-grid').addEventListener('click', function() {
        document.getElementById('files-container').classList.add('grid-view');
        document.getElementById('files-container').classList.remove('list-view');
        this.classList.add('bg-gray-100', 'text-gray-600');
        this.classList.remove('text-gray-400');
        document.getElementById('toggle-view-list').classList.add('text-gray-400');
        document.getElementById('toggle-view-list').classList.remove('bg-gray-100', 'text-gray-600');
    });
    
    document.getElementById('toggle-view-list').addEventListener('click', function() {
        document.getElementById('files-container').classList.add('list-view');
        document.getElementById('files-container').classList.remove('grid-view');
        this.classList.add('bg-gray-100', 'text-gray-600');
        this.classList.remove('text-gray-400');
        document.getElementById('toggle-view-grid').classList.add('text-gray-400');
        document.getElementById('toggle-view-grid').classList.remove('bg-gray-100', 'text-gray-600');
    });
    */
    
    // Mostrar/ocultar preview de archivo
    const selectFileBtn = document.querySelector('.select-file-btn');
    if (selectFileBtn) {
        selectFileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('fileInput').click();
        });
    }
    
    // Añade este código para asegurar que el input se actualice correctamente
const fileInput = document.getElementById('fileInput');
if (fileInput) {
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        
        if (file) {
            console.log("Archivo seleccionado:", file.name, file.type);
            const fileSelected = document.getElementById('file-selected');
            const fileSize = document.getElementById('file-size');
            
            if (fileSelected) fileSelected.textContent = file.name;
            
            // Mostrar tamaño formateado
            if (fileSize) {
                const size = file.size < 1024 ? file.size + ' bytes' :
                            file.size < 1024 * 1024 ? (file.size / 1024).toFixed(2) + ' KB' :
                            (file.size / (1024 * 1024)).toFixed(2) + ' MB';
                fileSize.textContent = size;
            }
            
            document.getElementById('file-preview').classList.remove('hidden');
            document.getElementById('uploadBtn').disabled = !document.getElementById('selected-folder-id').value;
        }
    });
}
    
    document.getElementById('remove-file').addEventListener('click', function() {
        document.getElementById('fileInput').value = '';
        document.getElementById('file-preview').classList.add('hidden');
        document.getElementById('uploadBtn').disabled = true;
    });
    
    // Filtro de búsqueda para archivos
    const fileSearchElement = document.getElementById('file-search');
    if (fileSearchElement) {
        fileSearchElement.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const fileItems = document.querySelectorAll('.file-card');
            
            fileItems.forEach(item => {
                const fileName = item.querySelector('.file-name').textContent.toLowerCase();
                if (fileName.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Filtro de búsqueda para modal de carpetas
    document.getElementById('modal-folder-search').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const folderItems = document.querySelectorAll('.folder-item');
        
        folderItems.forEach(item => {
            const folderName = item.querySelector('.folder-name').textContent.toLowerCase();
            if (folderName.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // Actualización automática del estado de la ubicación actual para nuevas carpetas
    function updateCurrentLocationDisplay() {
        const currentLocationEl = document.getElementById('current-location');
        
        if (breadcrumbPath.length > 0) {
            currentLocationEl.textContent = breadcrumbPath[breadcrumbPath.length - 1].name;
        } else {
            currentLocationEl.textContent = 'Raíz';
        }
    }
    
    // Cada vez que cambiamos de pestaña, actualizamos la ubicación
    document.getElementById('tab-folder').addEventListener('click', function() {
        updateCurrentLocationDisplay();
    });
    
    // Event listeners para el selector de ubicación
    document.getElementById('useCurrentLocation').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('folder-location-selector').classList.add('opacity-50', 'pointer-events-none');
        }
    });

    document.getElementById('selectDifferentLocation').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('folder-location-selector').classList.remove('opacity-50', 'pointer-events-none');
        }
    });
});

    document.addEventListener('usuarioAutenticado', function () {
        // No redeclarar las variables con 'let', solo asignar valores
        currentFolderId = null;
        breadcrumbPath = [];
        modalCurrentFolderId = null;
        modalBreadcrumbPath = [];
        selectedFolderId = null;
        selectedFolderName = null;
        
        // Inicializar la interfaz cuando el usuario está autenticado
        initInterface();
        
        function initInterface() {
            // Event listeners para tabs (añadir el tab-trash)
            document.getElementById('tab-files').addEventListener('click', function() {
                showTab('files');
            });
            
            document.getElementById('tab-upload').addEventListener('click', function() {
                showTab('upload');
            });
            
            document.getElementById('tab-folder').addEventListener('click', function() {
                showTab('folder');
            });
            
            document.getElementById('tab-trash').addEventListener('click', function() {
                showTab('trash');
            });
            
            // Actualizar lista de archivos
            document.getElementById('refresh-files').addEventListener('click', function() {
                listFiles(currentFolderId);
            });
            
            // Mostrar nombre del archivo seleccionado
            document.getElementById('fileInput').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Habilitar el botón de subida solo si se ha seleccionado carpeta
                    document.getElementById('uploadBtn').disabled = !document.getElementById('selected-folder-id').value;
                } else {
                    document.getElementById('uploadBtn').disabled = true;
                }
            });
            
            // Botón para seleccionar carpeta
            document.getElementById('select-folder-btn').addEventListener('click', function() {
                openFolderSelectionModal('upload');
            });

            // Cerrar el modal de selección de carpeta
            document.getElementById('close-folder-modal').addEventListener('click', function() {
                document.getElementById('folder-select-modal').style.display = 'none';
            });

            // Cancelar selección de carpeta
            document.getElementById('cancel-folder-select').addEventListener('click', function() {
                document.getElementById('folder-select-modal').style.display = 'none';
            });

            // Confirmar selección de carpeta
            document.getElementById('confirm-folder-select').addEventListener('click', function() {
                confirmFolderSelection();
            });

            // Botón para crear nueva carpeta desde el modal
            document.getElementById('create-folder-button').addEventListener('click', function() {
                document.getElementById('folder-select-modal').style.display = 'none';
                showTab('folder');
            });
            
            // Botón para seleccionar ubicación de la carpeta nueva
            document.getElementById('folder-select-location-btn').addEventListener('click', function() {
                // Establecer el modo para selección de ubicación de carpeta
                window.currentSelectionMode = 'folderLocation';
                
                // Mostrar el modal y cargar carpetas
                document.getElementById('folder-select-modal').style.display = 'block';
                listFoldersInModal();
            });
            
            // Añadir al initInterface - evento para vaciar papelera
            document.getElementById('empty-trash').addEventListener('click', function() {
                if (confirm("¿Estás seguro de vaciar la papelera? Esta acción eliminará permanentemente todos los archivos.")) {
                    emptyTrash();
                }
            });
            
            // Cargar archivos inicialmente
            listFiles(currentFolderId);
        }
           });
    
    // Función para listar archivos/carpetas
    async function listFiles(parentId = null) {
        console.log(`Listando archivos y carpetas para parentId: ${parentId || 'root'}`);
        const filesContainer = document.getElementById('files-container');
        
        if (!filesContainer) {
            console.error("Contenedor de archivos no encontrado");
            return;
        }
        
        // Mostrar indicador de carga
        filesContainer.innerHTML = `
            <div class="text-center text-gray-500 py-10 col-span-full">
                <div class="flex justify-center mb-3">
                    <div class="loading-spinner"></div>
                </div>
                <p>Cargando archivos...</p>
            </div>
        `;
        
        try {
            const token = localStorage.getItem('auth_token');
            
            if (!token) {
                filesContainer.innerHTML = `<div class="text-center py-10 col-span-full">Inicia sesión para ver tus archivos</div>`;
                return;
            }
            
            // Construir las URLs
            const foldersUrl = `http://127.0.0.1:8000/api/list-folders${parentId ? `?parent_id=${parentId}` : ''}`;
            const filesUrl = `http://127.0.0.1:8000/api/list-files${parentId ? `?parent_id=${parentId}` : ''}`;
            
            // Definir las opciones de la petición
            const requestOptions = {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            };
            
            // Cargar carpetas y archivos en paralelo
            const [foldersResponse, filesResponse] = await Promise.all([
                fetch(foldersUrl, requestOptions),
                fetch(filesUrl, requestOptions)
            ]);
            
            const foldersData = await foldersResponse.json();
            const filesData = await filesResponse.json();
            
            console.log("Carpetas recibidas:", foldersData.folders?.length || 0);
            console.log("Archivos recibidos:", filesData.files?.length || 0);
            
            // Array para almacenar todos los elementos
            const combinedItems = [];
            
            // Añadir carpetas (garantizando que no haya duplicados)
            if (foldersData.success && foldersData.folders) {
                // Usar un Set para almacenar IDs ya procesados
                const processedIds = new Set();
                
                foldersData.folders.forEach(folder => {
                    // Verificar si esta carpeta ya se agregó
                    if (!processedIds.has(folder.id)) {
                        processedIds.add(folder.id);
                        combinedItems.push({
                            ...folder,
                            isFolder: true,
                            mimeType: 'application/vnd.google-apps.folder'
                        });
                    }
                });
            }
            
            // Añadir archivos (excluyendo carpetas)
            if (filesData.success && filesData.files) {
                // Filtrar para no incluir carpetas que ya podrían estar en el array
                const processedIds = new Set(combinedItems.map(item => item.id));
                
                filesData.files.forEach(file => {
                    // Solo añadir si no es una carpeta o si aún no se ha agregado
                    if (file.mimeType !== 'application/vnd.google-apps.folder' || !processedIds.has(file.id)) {
                        processedIds.add(file.id);
                        combinedItems.push(file);
                    }
                });
            }
            
            // Mostrar en la interfaz
            displayFiles(combinedItems);
            
        } catch (error) {
            console.error("Error al cargar archivos:", error);
            filesContainer.innerHTML = handlePermissionError(error);
        }
    }
    
    // Función para manejar errores de permisos
    function handlePermissionError(error) {
        return `
            <div class="text-center py-10 col-span-full">
                <div class="bg-red-50 p-6 rounded-lg inline-block">
                    <i class="fas fa-exclamation-circle text-red-500 text-5xl mb-4"></i>
                    <p class="text-lg font-medium text-red-800">Error de permisos</p>
                    <p class="text-sm text-red-700 mt-2">
                        No tienes acceso a este recurso. Solo puedes ver y gestionar tus propios archivos.
                    </p>
                    <button onclick="listFiles(null)" class="mt-4 bg-red-100 text-red-700 px-4 py-2 rounded-md hover:bg-red-200 transition">
                        <i class="fas fa-home mr-2"></i>Volver al inicio
                    </button>
                </div>
            </div>
        `;
    }
    
    // Función mejorada para mostrar archivos en UI
    function displayFiles(items) {
        console.log("Mostrando items:", items);
        const filesContainer = document.getElementById('files-container');
        
        if (!filesContainer) {
            console.error("Contenedor de archivos no encontrado");
            return;
        }
        
        if (!items || items.length === 0) {
            filesContainer.innerHTML = `
                <div class="text-center py-10 col-span-full">
                    <div class="p-6 rounded-lg inline-block">
                        <div class="text-gray-400 text-6xl mb-4">
                            <i class="far fa-folder-open"></i>
                        </div>
                        <h3 class="text-xl font-medium text-gray-600 mb-1">Carpeta vacía</h3>
                        <p class="text-gray-500">No hay archivos ni carpetas en esta ubicación</p>
                    </div>
                </div>
            `;
            return;
        }
        
        // Ordenar: carpetas primero, luego archivos por nombre
        items.sort((a, b) => {
            // Primero por tipo (carpetas primero)
            if (a.isFolder && !b.isFolder) return -1;
            if (!a.isFolder && b.isFolder) return 1;
            if ((a.mimeType === 'application/vnd.google-apps.folder') && 
                (b.mimeType !== 'application/vnd.google-apps.folder')) return -1;
            if ((a.mimeType !== 'application/vnd.google-apps.folder') && 
                (b.mimeType === 'application/vnd.google-apps.folder')) return 1;
            
            // Luego por nombre
            return a.name.localeCompare(b.name);
        });
        
        // Usar un Set para verificar elementos ya renderizados
        const renderedIds = new Set();
        
        let html = '';
        items.forEach(item => {
            // Evitar duplicados
            if (renderedIds.has(item.id)) return;
            renderedIds.add(item.id);
            
            const isFolder = item.isFolder || item.mimeType === 'application/vnd.google-apps.folder';
            const iconClass = isFolder ? 'fas fa-folder folder-icon' : getFileIconClass(item.mimeType, item.name);
            const itemType = isFolder ? 'Carpeta' : getFileType(item.name);
            const itemDate = new Date(item.modifiedTime || item.createdTime);
            const formattedDate = itemDate.toLocaleDateString('es-ES', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric' 
            });
            
            // Verificar si es un archivo Excel (mantenemos la detección pero no añadimos vista previa)
            const isExcel = !isFolder && /\.(xlsx|xls)$/i.test(item.name);
            const cardClass = isExcel ? 'border-emerald-100' : (isFolder ? 'border-yellow-100' : 'border-gray-100');
            const iconBgClass = isExcel ? 'bg-emerald-50' : (isFolder ? 'bg-yellow-50' : 'bg-blue-50');
            const actionFunction = isFolder 
                ? `navigateToFolder('${item.id}', '${item.name.replace(/'/g, "\\'")}')`
                : `openFile('${item.id}')`;
            
            html += `
                <div class="file-card bg-white rounded-lg shadow-sm border ${cardClass} hover:border-gray-300 hover:shadow-md" 
                     ondblclick="${actionFunction}" data-id="${item.id}">
                    <div class="p-4 flex flex-col h-full">
                        <!-- Cabecera con icono y nombre -->
                        <div class="flex items-start mb-2">
                            <div class="w-12 h-12 rounded-lg ${iconBgClass} flex items-center justify-center mr-3 flex-shrink-0">
                                <i class="${iconClass} text-2xl"></i>
                            </div>
                            <div class="flex-grow overflow-hidden">
                                <h3 class="font-medium text-gray-800 mb-1 file-name truncate" title="${item.name}">
                                    ${item.name}
                                </h3>
                                <div class="flex items-center text-xs text-gray-500">
                                    <span class="rounded-full px-2 py-0.5 ${isFolder ? 'bg-yellow-50 text-yellow-700' : (isExcel ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700')}">
                                        <i class="${isFolder ? 'fas fa-folder-open' : (isExcel ? 'fas fa-table' : 'fas fa-file')} mr-1"></i>
                                        ${itemType}
                                    </span>
                                    <span class="mx-1">•</span>
                                    <span title="Fecha de modificación"><i class="far fa-clock mr-1"></i>${formattedDate}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-auto pt-3 border-t border-gray-100">
                            <!-- Acciones de archivo -->
                            <div class="flex justify-between items-center">
                                <div class="text-xs text-gray-500">
                                    <i class="fas fa-user mr-1"></i> Tú
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="${actionFunction}" 
                                            class="text-xs py-1 px-2.5 rounded text-white bg-blue-500 hover:bg-blue-600 transition-colors">
                                        <i class="${isFolder ? 'fas fa-folder-open' : 'fas fa-external-link-alt'} mr-1"></i>
                                        ${isFolder ? 'Abrir' : 'Ver'}
                                    </button>
                                    <button onclick="event.stopPropagation(); confirmMoveToTrash('${item.id}', '${item.name.replace(/'/g, "\\'")}', ${isFolder})" 
                                            class="text-xs py-1 px-2.5 rounded text-gray-700 bg-gray-200 hover:bg-gray-300 transition-colors">
                                        <i class="fas fa-trash mr-1"></i>
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        filesContainer.innerHTML = html;
        
        // Activar doble clic para abrir archivos/carpetas
        enableFileDoubleClick();
    }
    
    // Efecto visual al hacer doble clic
function addDblClickEffect(element) {
    element.classList.add('dblclick-effect');
    setTimeout(() => {
        element.classList.remove('dblclick-effect');
    }, 300);
}

// Modificar la función de doble clic
function enableFileDoubleClick() {
    document.querySelectorAll('.file-card').forEach(card => {
        card.addEventListener('dblclick', function(e) {
            e.stopPropagation();
            
            // Efecto visual
            addDblClickEffect(this);
            
            const isFolder = this.querySelector('.folder-icon') !== null;
            const id = this.getAttribute('data-id');
            const name = this.querySelector('.file-name').textContent;
            
            // Pequeño retraso para que se vea el efecto antes de la acción
            setTimeout(() => {
                if (isFolder) {
                    navigateToFolder(id, name);
                } else {
                    openFile(id);
                }
            }, 150);
        });
    });
}

// Mostrar tab específico
function showTab(tabName) {
    console.log("Mostrando tab:", tabName);
    
    // Ocultar todos los contenidos
    const sections = ['files-section', 'upload-section', 'folder-section', 'trash-section'];
    sections.forEach(section => {
        const element = document.getElementById(section);
        if (element) {
            element.classList.add('hidden');
            console.log(`Ocultando sección ${section}`);
        } else {
            console.warn(`Elemento ${section} no encontrado`);
        }
    });
    
    // Mostrar el contenido seleccionado
    const selectedSection = document.getElementById(`${tabName}-section`);
    if (selectedSection) {
        selectedSection.classList.remove('hidden');
        console.log(`Mostrando sección ${tabName}-section`);
    } else {
        console.error(`Sección ${tabName}-section no encontrada`);
        return; // Salir si no existe la sección
    }
    
    // Actualizar estados de los tabs
    const tabButtons = document.querySelectorAll('.tab-button');
    if (tabButtons && tabButtons.length) {
        tabButtons.forEach(tab => {
            if (tab) tab.classList.remove('tab-active');
        });
        
        const activeTab = document.getElementById(`tab-${tabName}`);
        if (activeTab) activeTab.classList.add('tab-active');
    }
    
    // Si estamos mostrando archivos o papelera, cargarlos
    if (tabName === 'files') {
        listFiles(currentFolderId);
    } else if (tabName === 'trash') {
        if (typeof listTrashFiles === 'function') {
            listTrashFiles();
        } else {
            console.error("La función listTrashFiles no está definida");
        }
    }
}

// Mostrar mensaje de feedback mejorado
function showFeedback(elementId, type, message) {
    const feedbackEl = document.getElementById(elementId);
    feedbackEl.classList.remove('hidden', 'feedback-success', 'feedback-error');
    
    if (type === 'success') {
        feedbackEl.classList.add('feedback-success');
        feedbackEl.innerHTML = message;
    } else {
        feedbackEl.classList.add('feedback-error');
        feedbackEl.innerHTML = message;
    }
    
    feedbackEl.classList.remove('hidden');
    
    // Auto-ocultar después de un tiempo
    setTimeout(() => {
        feedbackEl.classList.add('fade-out');
        setTimeout(() => {
            feedbackEl.classList.add('hidden');
            feedbackEl.classList.remove('fade-out');
        }, 500);
    }, 8000);
}

// Modal para seleccionar carpeta
function openFolderSelectionModal(modo = 'upload') {
    // Reiniciar estado del modal
    modalCurrentFolderId = null;
    modalBreadcrumbPath = [];
    selectedFolderId = null;
    selectedFolderName = null;
    
    // Establecer el modo de selección actual
    window.currentSelectionMode = modo;
    
    // Actualizar título del modal según el modo
    const modalTitle = document.querySelector('#folder-select-modal h2');
    if (modo === 'folderLocation') {
        modalTitle.textContent = 'Seleccionar ubicación para nueva carpeta';
    } else {
        modalTitle.textContent = 'Seleccionar carpeta destino';
    }
    
    // Mostrar el modal y cargar carpetas
    document.getElementById('folder-select-modal').style.display = 'block';
    listFoldersInModal();
}

// Función para listar archivos en papelera
async function listTrashFiles() {
    console.log("Listando archivos en papelera");
    const trashContainer = document.getElementById('trash-container');
    
    if (!trashContainer) {
        console.error("Contenedor de papelera no encontrado");
        return;
    }
    
    // Mostrar indicador de carga
    trashContainer.innerHTML = `
        <div class="text-center text-gray-500 py-10 col-span-full">
            <div class="flex justify-center mb-3">
                <div class="loading-spinner"></div>
            </div>
            <p>Cargando papelera...</p>
        </div>
    `;
    
    try {
        const token = localStorage.getItem('auth_token');
        
        if (!token) {
            console.error("Token de autenticación no encontrado");
            trashContainer.innerHTML = `
                <div class="text-center py-10 col-span-full">
                    <div class="bg-red-50 p-6 rounded-lg inline-block">
                        <i class="fas fa-exclamation-circle text-red-500 text-5xl mb-4"></i>
                        <p class="text-lg">Error: No hay sesión activa</p>
                        <p class="text-sm text-red-500">Por favor inicia sesión nuevamente</p>
                    </div>
                </div>
            `;
            return;
        }
        
        console.log("Enviando solicitud API para listar papelera");
        
        const response = await fetch('http://127.0.0.1:8000/api/list-trash', {
            method: 'GET',
            headers: {
                "Authorization": "Bearer " + token,
                "Content-Type": "application/json",
                "Accept": "application/json"
            }
        }).catch(error => {
            console.log("Error en primera solicitud, intentando ruta alternativa:", error);
            // Intentar con ruta alternativa
            return fetch('http://127.0.0.1:8000/api/trash', {
                method: 'GET',
                headers: {
                    "Authorization": "Bearer " + token,
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                }
            });
        });
        
        // Verificar si la respuesta es JSON válido
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            const text = await response.text();
            console.error("Respuesta no JSON:", text);
            throw new Error("Respuesta del servidor no es JSON válido");
        }
        
        const result = await response.json();
        console.log("Respuesta de API:", result);
        
        if (result.success) {
            displayTrashFiles(result.files || []);
        } else {
            trashContainer.innerHTML = `
                <div class="text-center py-10 col-span-full">
                    <div class="bg-red-50 p-6 rounded-lg inline-block">
                        <i class="fas fa-exclamation-circle text-red-500 text-5xl mb-4"></i>
                        <p class="text-lg">Error: ${result.error || 'No se pudieron cargar los archivos de la papelera'}</p>
                        <button onclick="listTrashFiles()" class="mt-4 bg-red-100 text-red-700 px-4 py-2 rounded-md hover:bg-red-200 transition">
                            <i class="fas fa-redo mr-2"></i>Reintentar
                        </button>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error("Error al listar archivos en papelera:", error);
        trashContainer.innerHTML = `
            <div class="text-center py-10 col-span-full">
                <div class="bg-red-50 p-6 rounded-lg inline-block">
                    <i class="fas fa-exclamation-circle text-red-500 text-5xl mb-4"></i>
                    <p class="text-lg">Error de conexión: ${error.message}</p>
                    <button onclick="listTrashFiles()" class="mt-4 bg-red-100 text-red-700 px-4 py-2 rounded-md hover:bg-red-200 transition">
                        <i class="fas fa-redo mr-2"></i>Reintentar
                    </button>
                </div>
            </div>
        `;
    }
}

    // Función para mostrar archivos de papelera
    function displayTrashFiles(files) {
        console.log("Mostrando archivos en papelera:", files);
        const trashContainer = document.getElementById('trash-container');
        
        if (!trashContainer) {
            console.error("Contenedor de papelera no encontrado");
            return;
        }
        
        if (!files || files.length === 0) {
            trashContainer.innerHTML = `
                <div class="text-center text-gray-500 py-10 col-span-full">
                    <div class="py-6">
                        <i class="fas fa-trash-alt text-5xl mb-4 text-gray-300"></i>
                        <p class="text-lg">La papelera está vacía</p>
                        <p class="text-sm text-gray-400 mt-2">Los elementos eliminados aparecerán aquí</p>
                    </div>
                </div>
            `;
            return;
        }

    // Ordenar archivos
    files.sort((a, b) => new Date(b.modifiedTime || b.createdTime) - new Date(a.modifiedTime || a.createdTime));
    
    let html = '';
    files.forEach(file => {
        const isFolder = file.mimeType === 'application/vnd.google-apps.folder';
        const iconClass = isFolder ? 'fas fa-folder folder-icon' : 'fas fa-file-excel file-excel';
        const fileType = isFolder ? 'Carpeta' : getFileType(file.name);
        
        const trashDate = new Date(file.trashedTime || file.modifiedTime || file.createdTime || new Date());
        const formattedDate = trashDate.toLocaleDateString('es-ES', {
            day: '2-digit', 
            month: '2-digit', 
            year: 'numeric'
        });
        
        html += `
            <div class="file-card bg-white rounded-lg shadow-sm border border-gray-100 hover:border-gray-300 overflow-hidden opacity-80">
                <div class="p-4">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center mr-3 ${isFolder ? 'bg-yellow-50' : 'bg-blue-50'}">
                                <i class="${iconClass} text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-medium text-gray-800 file-name">${file.name}</h3>
                                <p class="text-xs text-gray-500 mt-1">Eliminado: ${formattedDate}</p>
                            </div>
                        </div>
                        
                        <div class="file-dropdown relative">
                            <button class="p-1.5 rounded-full hover:bg-gray-100 text-gray-500 file-dropdown-toggle">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="file-dropdown-menu" style="display: none">
                                <div class="file-dropdown-item" onclick="restoreFile('${file.id}')">
                                    <i class="fas fa-trash-restore"></i>
                                    <span>Restaurar</span>
                                </div>
                                <div class="file-dropdown-item text-red-500" onclick="confirmPermanentDelete('${file.id}', '${file.name.replace(/'/g, "\\'")}')">
                                    <i class="fas fa-trash-alt"></i>
                                    <span>Eliminar permanentemente</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-xs text-gray-500 mb-3">
                        <span class="inline-block px-2 py-1 rounded-full ${isFolder ? 'bg-yellow-50 text-yellow-700' : 'bg-blue-50 text-blue-700'}">
                            ${fileType}
                        </span>
                        <span class="inline-block px-2 py-1 ml-1 rounded-full bg-red-50 text-red-700">
                            <i class="fas fa-trash mr-1"></i>En papelera
                        </span>
                        <span class="inline-block px-2 py-1 ml-1 rounded-full bg-green-50 text-green-700" title="Este archivo te pertenece">
                            <i class="fas fa-user-check mr-1"></i>Propietario
                        </span>
                    </div>
                </div>
            </div>
        `;
    });
    
    trashContainer.innerHTML = html;
    
    // Activar dropdowns para acciones de archivos
    activateDropdowns();
}

    // Función para restaurar archivo desde la papelera
    window.restoreFile = async function(fileId) {
        try {
            const token = localStorage.getItem('auth_token');
            const response = await fetch('http://127.0.0.1:8000/api/restore-file', {
                method: 'POST',
                headers: {
                    "Authorization": "Bearer " + token,
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    file_id: fileId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Recargar la lista de archivos en papelera
                listTrashFiles();
            } else {
                alert(`Error al restaurar el archivo: ${result.error || 'Ocurrió un error desconocido'}`);
            }
        } catch (error) {
            console.error("Error al restaurar archivo:", error);
            alert('Error de conexión al intentar restaurar el archivo');
        }
    };

    // Función para confirmar eliminación permanente
    window.confirmPermanentDelete = function(fileId, fileName) {
        if (confirm(`¿Estás seguro de eliminar permanentemente "${fileName}"? Esta acción no se puede deshacer.`)) {
            permanentlyDeleteFile(fileId);
        }
    };

    // Función para eliminar archivo permanentemente
    async function permanentlyDeleteFile(fileId) {
        try {
            const token = localStorage.getItem('auth_token');
            const response = await fetch('http://127.0.0.1:8000/api/permanently-delete-file', {
                method: 'DELETE',
                headers: {
                    "Authorization": "Bearer " + token,
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    file_id: fileId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Recargar la lista de archivos en papelera
                listTrashFiles();
            } else {
                alert(`Error al eliminar el archivo permanentemente: ${result.error || 'Ocurrió un error desconocido'}`);
            }
        } catch (error) {
            console.error("Error al eliminar archivo permanentemente:", error);
            alert('Error de conexión al intentar eliminar el archivo');
        }
    }

    // Añadir al initInterface
    document.getElementById('empty-trash').addEventListener('click', function() {
        if (confirm("¿Estás seguro de vaciar la papelera? Esta acción eliminará permanentemente todos los archivos.")) {
            emptyTrash();
        }
    });

    // Función para vaciar la papelera
    async function emptyTrash() {
        try {
            const token = localStorage.getItem('auth_token');
            const response = await fetch('http://127.0.0.1:8000/api/empty-trash', {
                method: 'DELETE',
                headers: {
                    "Authorization": "Bearer " + token,
                    "Content-Type": "application/json"
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Recargar la lista de archivos en papelera
                listTrashFiles();
            } else {
                alert(`Error al vaciar la papelera: ${result.error || 'Ocurrió un error desconocido'}`);
            }
        } catch (error) {
            console.error("Error al vaciar la papelera:", error);
            alert('Error de conexión al intentar vaciar la papelera');
        }
    }

    window.confirmMoveToTrash = function(fileId, fileName, isFolder) {
        const itemType = isFolder ? "carpeta" : "archivo";
        if (confirm(`¿Estás seguro de mover ${itemType} "${fileName}" a la papelera?`)) {
            moveToTrash(fileId);
        }
    };

    async function moveToTrash(fileId) {
        try {
            const token = localStorage.getItem('auth_token');
            const response = await fetch('http://127.0.0.1:8000/api/trash-file', {
                method: 'POST',
                headers: {
                    "Authorization": "Bearer " + token,
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    file_id: fileId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Recargar lista de archivos actuales
                listFiles(currentFolderId);
            } else {
                alert(`Error al mover a la papelera: ${result.error || 'Ocurrió un error desconocido'}`);
            }
        } catch (error) {
            console.error("Error al mover a papelera:", error);
            alert('Error de conexión al intentar mover el archivo a la papelera');
        }
    }

    // Función para actualizar la navegación de migas de pan (breadcrumb)
function updateBreadcrumb() {
    console.log("Actualizando breadcrumb", breadcrumbPath);
    const breadcrumbContainer = document.getElementById('breadcrumb-container');
    
    if (!breadcrumbContainer) {
        console.error("Contenedor breadcrumb no encontrado");
        return;
    }
    
    let html = `
        <div class="breadcrumb-item cursor-pointer" onclick="navigateToFolder(null, 'Raíz')">
            <i class="fas fa-home"></i>
            <span>Raíz</span>
        </div>
    `;
    
    // Agregar cada nivel del breadcrumb
    breadcrumbPath.forEach((item, index) => {
        html += `<div class="breadcrumb-separator">/</div>`;
        html += `
            <div class="breadcrumb-item cursor-pointer ${index === breadcrumbPath.length - 1 ? 'text-blue-600 font-medium' : ''}" 
                onclick="navigateToFolder('${item.id}', '${item.name}')">
                <span>${item.name}</span>
            </div>
        `;
    });

    
    breadcrumbContainer.innerHTML = html;
    
    // Actualizar también título de la sección
    const titleElement = document.getElementById('section-title');
    if (titleElement) {
        if (breadcrumbPath.length > 0) {
           
            titleElement.textContent = breadcrumbPath[breadcrumbPath.length - 1].name;
        } else {
            titleElement.textContent = 'Mis archivos personales';
        }
    }
}

// Función para navegar a una carpeta específica
window.navigateToFolder = function(folderId, folderName) {
    console.log(`Navegando a carpeta: ${folderName || 'Raíz'} (ID: ${folderId || 'null'})`);
    
    if (folderId === null) {
        // Volver a la raíz
        currentFolderId = null;
        breadcrumbPath = [];
    } else {
        // Si ya existe en el breadcrumb, cortar hasta
        const existingIndex = breadcrumbPath.findIndex(item => item.id === folderId);
        
        if (existingIndex >= 0) {
            breadcrumbPath = breadcrumbPath.slice(0, existingIndex + 1);
        } else {
            // Agregar nuevo nivel al breadcrumb
            breadcrumbPath.push({
                id: folderId,
                name: folderName
            });
        }
        
        currentFolderId = folderId;
    }
    
    // Actualizar la interfaz
    updateBreadcrumb();
    listFiles(currentFolderId);
};

    // Función para actualizar el breadcrumb del modal
    function updateModalBreadcrumb() {
        const breadcrumbContainer = document.getElementById('modal-breadcrumb');
        
        if (!breadcrumbContainer) {
            console.error("Contenedor de breadcrumb del modal no encontrado");
            return;
        }
        
        let html = `
            <span class="cursor-pointer hover:text-blue-500 mb-1 breadcrumb-item" 
                  onclick="modalNavigateToFolder(null, 'Raíz')">
                <i class="fas fa-home mr-1"></i>Raíz
            </span>
        `;
        
        modalBreadcrumbPath.forEach((item, index) => {
            html += `<span class="mx-2 text-gray-400">/</span>`;
            html += `
                <span class="cursor-pointer hover:text-blue-500 mb-1 breadcrumb-item ${index === modalBreadcrumbPath.length - 1 ? 'text-blue-600 font-medium' : ''}" 
                      onclick="modalNavigateToFolder('${item.id}', '${item.name}')">
                    ${item.name}
                </span>
            `;
        });
        
        breadcrumbContainer.innerHTML = html;
    }

    // Función para navegar entre carpetas en el modal
    window.modalNavigateToFolder = function(folderId, folderName) {
        if (folderId === null) {
            // Volver a la raíz
            modalCurrentFolderId = null;
            modalBreadcrumbPath = [];
        } else {
            // Si ya
            const existingIndex = modalBreadcrumbPath.findIndex(item => item.id === folderId);
            
            if (existingIndex >= 0) {
                modalBreadcrumbPath = modalBreadcrumbPath.slice(0, existingIndex + 1);
            } else {
                // Agregar nuevo nivel al breadcrumb
                modalBreadcrumbPath.push({
                    id: folderId,
                    name: folderName
                });
            }
            
            modalCurrentFolderId = folderId;
        }
        
        // Actualizar UI y cargar carpetas
        updateModalBreadcrumb();
        listFoldersInModal(modalCurrentFolderId);
    }

    // Función para habilitar navegación por doble clic en las carpetas del modal
function enableModalFolderNavigation() {
    console.log("Configurando navegación por doble clic en carpetas del modal");
    
    document.querySelectorAll('.folder-item').forEach(item => {
        // Eliminar eventos previos para evitar duplicados
        item.removeEventListener('dblclick', folderNavigationHandler);
        
        // Agregar evento de doble clic para navegar
        item.addEventListener('dblclick', folderNavigationHandler);
    });
}

// Manejador separado para el evento de doble clic
function folderNavigationHandler(e) {
    e.stopPropagation(); // Evitar propagación para que no se active también el clic simple
    
    const folderId = this.getAttribute('data-folder-id');
    const folderName = this.querySelector('.folder-name').textContent;
    
    console.log(`Doble clic en carpeta: "${folderName}" (ID: ${folderId || 'root'})`);
    modalNavigateToFolder(folderId, folderName);
}

    function getFileType(fileName) {
    if (!fileName) return 'Archivo';
    
    const extension = fileName.split('.').pop().toLowerCase();
    
    switch (extension) {
        case 'xlsx':
        case 'xls':
            return 'Hoja de cálculo';
        case 'docx':
        case 'doc':
            return 'Documento';
        case 'pptx':
        case 'ppt':
            return 'Presentación';
        case 'pdf':
            return 'PDF';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return 'Imagen';
        case 'zip':
        case 'rar':
            return 'Archivo comprimido';
        case 'txt':
            return 'Texto';
        default:
            return 'Archivo';
    }
}

// Función para formatear tamaños de archivo
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];

    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// Función para listar carpetas en el modal
async function listFoldersInModal(parentId = null) {
    const foldersContainer = document.getElementById('modal-folders-container');
    
    if (!foldersContainer) {
        console.error("Contenedor de carpetas del modal no encontrado");
        return;
    }
    
    // Mostrar indicador de carga
    foldersContainer.innerHTML = `
        <div class="text-center text-gray-500 py-10 col-span-full">
            <div class="flex justify-center mb-3">
                <div class="loading-spinner"></div>
            </div>
            <p>Cargando carpetas...</p>
        </div>
    `;
    
    try {
        const token = localStorage.getItem('auth_token');
        const url = `http://127.0.0.1:8000/api/list-folders${parentId ? `?parent_id=${parentId}` : ''}`;
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                               "Authorization": "Bearer " + token,
                "Content-Type": "application/json",
                "Accept": "application/json"
            }
        });
        
        // Verificar si hay errores de permisos (403)
        if (response.status === 403) {
            foldersContainer.innerHTML = `
                <div class="text-center py-10 col-span-full">
                    <div class="bg-red-50 p-6 rounded-lg inline-block">
                        <p class="red-700">No tienes permiso para ver estas carpetas</p>
                    </div                </div>
            `;
            return;
        }
        
        const result = await response.json();
        
        
        
        
        if (result.success) {
            displayFoldersInModal(result.folders || []);
            updateModalBreadcrumb();
        } else {
            foldersContainer.innerHTML = `
                <div class="text-center py-10 col-span-full">
                    <div class="bg-red-50 p-6 rounded-lg inline-block">
                        <i class="fas fa-exclamation-circle text-red-500 text-5xl mb-4"></i>
                        <p class="text-lg">Error: ${result.error || 'No se pudieron cargar las carpetas'}</p>
                        <button onclick="listFoldersInModal(${parentId})" class="mt-4 bg-red-100 text-red-700 px-4 py-2 rounded-md hover:bg-red-200 transition">
                            <i class="fas fa-redo mr-2"></i>Reintentar
                        </button>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error("Error al cargar carpetas:", error);
        foldersContainer.innerHTML = `
            <div class="text-center py-10 col-span-full">
                <p class="text-red-500">Error de conexión al cargar carpetas</p>
            </div>
        `;
    }
}

// Función para mostrar carpetas en el modal
function displayFoldersInModal(folders) {
    const foldersContainer = document.getElementById('modal-folders-container');
    
    if (!folders || folders.length === 0) {
        foldersContainer.innerHTML = `
            <div class="text-center py-8 col-span-full">
                <div class="inline-block p-6 rounded-lg bg-gray-50">
                    <i class="far fa-folder-open text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-500">No hay carpetas disponibles</p>
                    <p class="text-gray-400 text-sm mt-2">
                        ${modalCurrentFolderId ? 'Esta carpeta está vacía' : 'No hay carpetas en la raíz'}
                    </p>
                </div>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    // Siempre mostrar opción de carpeta raíz
    html += `
        <div class="folder-item p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-blue-50" 
             data-folder-id="" onclick="selectModalFolder(this, '', 'Raíz')">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-lg bg-yellow-50 flex items-center justify-center mr-3">
                    <i class="fas fa-home text-yellow-500 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-medium text-gray-800 folder-name">Raíz</h3>
                    <p class="text-xs text-gray-500">Carpeta principal</p>
                </div>
            </div>
        </div>
    `;
    
    // Mostrar el resto de carpetas
    folders.forEach(folder => {
        html += `
        <div class="folder-item p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-blue-50" 
             data-folder-id="${folder.id}" onclick="selectModalFolder(this, '${folder.id}', '${folder.name.replace(/'/g, "\\'").replace(/"/g, '\\"')}')">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-lg bg-yellow-50 flex items-center justify-center mr-3">
                    <i class="fas fa-folder folder-icon text-xl"></i>
                </div>
                <div class="flex-grow">
                    <h3 class="font-medium text-gray-800 folder-name">${folder.name}</h3>
                    <div class="flex justify-between">
                        <p class="text-xs text-gray-500">Carpeta</p>
                        <p class="text-xs text-blue-400">
                            <i class="fas fa-mouse-pointer"></i> Doble clic para abrir
                        </p>
                    </div>
                </div>
            </div>
        </div>
    `;
    });
    
    foldersContainer.innerHTML = html;
    
    // IMPORTANTE: Activar la navegación por doble clic en las carpetas
    enableModalFolderNavigation();
}

// Función para seleccionar carpeta en el modal
window.selectModalFolder = function(element, folderId, folderName) {
    // Remover selección previa
    document.querySelectorAll('.folder-item').forEach(item => {
        item.classList.remove('selected', 'bg-blue-50', 'border-blue-300');
    });
    
    // Aplicar selección
    element.classList.add('selected', 'bg-blue-50', 'border-blue-300');
    
    // Guardar selección actual
    selectedFolderId = folderId;
    selectedFolderName = folderName;
};

    // Función para confirmar selección de carpeta
function confirmFolderSelection() {
    if (window.currentSelectionMode === 'folderLocation') {
        // Modo para ubicación de carpeta nueva
        document.getElementById('folder-selected-folder-id').value = selectedFolderId || '';
        
        
        if (selectedFolderName) {
            document.getElementById('folder-selected-folder-display').innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-folder text-yellow-500 mr-2"></i>
                    <span>${selectedFolderName}</span>
                </div>
            `;
        } else {
            document.getElementById('folder-selected-folder-display').innerHTML = '<p class="text-gray-500">Ninguna carpeta seleccionada</p>';
        }
    } else {
        // Modo para carpeta destino de archivo
        document.getElementById('selected-folder-id').value = selectedFolderId || '';
        
        if (selectedFolderName) {
            document.getElementById('selected-folder-display').innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-folder text-yellow-500 mr-2"></i>
                    <span>${selectedFolderName}</span>
                </div>
            `;
            // Habilitar botón de subida si hay archivo seleccionado
            const fileInput = document.getElementById('fileInput');
            if (fileInput.files.length > 0) {
                document.getElementById('uploadBtn').disabled = false;
            }
        } else {
            document.getElementById('selected-folder-display').innerHTML = '<p class="text-gray-500">Ninguna carpeta seleccionada</p>';
            document.getElementById('uploadBtn').disabled = true;
        }
    }
    
    // Cerrar modal
    document.getElementById('folder-select-modal').style.display = 'none';
}

// Función para abrir archivo en Google Drive
window.openFile = function(fileId) {
    window.open(`https://drive.google.com/file/d/${fileId}/view`, '_blank');
};

// Crear nueva carpeta
$('#folderForm').submit(async function (e) {
    e.preventDefault();

    const folderName = $('#folderName').val();
    if (!folderName) {
        showFeedback('folder-feedback', 'error', '<i class="fas fa-exclamation-circle mr-2"></i>Por favor, ingresa un nombre para la carpeta.');
        return;
    }

    $('#createFolderBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i><span>Creando...</span>');

    try {
        const token = localStorage.getItem('auth_token');
        const formData = new FormData();
        formData.append('name', folderName);
        
        // Determinar la ubicación basada en la selección del usuario
        let parentId = null;
        
        if (document.getElementById('useCurrentLocation').checked) {
            // Usar la carpeta actual de navegación
            parentId = currentFolderId;
        } else if (document.getElementById('selectDifferentLocation').checked) {
            // Usar la carpeta específicamente seleccionada
            parentId = document.getElementById('folder-selected-folder-id').value || null;
        }
        
        // Añadir el ID del padre solo si es un valor válido
        if (parentId !== null && parentId !== undefined && parentId !== '') {
            formData.append('parent_id', parentId);
        }
        
        // Llamada al endpoint create-folder del backend
        const response = await fetch('http://127.0.0.1:8000/api/create-folder', {
            method: 'POST',
            body: formData,
            headers: {
                "Authorization": "Bearer " + token
            }
        });

        const result = await response.json();
        
        if (result.success) {
            // Mostrar mensaje de éxito
            showFeedback('folder-feedback', 'success', `<i class="fas fa-check-circle mr-2"></i>Carpeta "${folderName}" creada correctamente.`);
            
            // Limpiar el formulario
            $('#folderName').val('');
            
            // Si estamos en la ubicación donde se creó la carpeta, actualizar la lista
            if (currentFolderId === parentId) {
                listFiles(currentFolderId);
            }
        } else {
            showFeedback('folder-feedback', 'error', `<i class="fas fa-exclamation-circle mr-2"></i>Error: ${result.error || 'No se pudo crear la carpeta'}`);
        }
    } catch (error) {
        console.error("Error al crear carpeta:", error);
        showFeedback('folder-feedback', 'error', '<i class="fas fa-exclamation-circle mr-2"></i>Error de conexión al crear la carpeta.');
    } finally {
        $('#createFolderBtn').prop('disabled', false).html('<i class="fas fa-folder-plus"></i><span>Crear Carpeta</span>');
    }
});

// Añade este código en el event listener del formulario de subida
document.getElementById('uploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const fileInput = document.getElementById('fileInput');
    if (!fileInput.files.length) {
        showFeedback('feedback', 'error', 'Por favor, selecciona un archivo.');
        return;
    }
    
    const file = fileInput.files[0];
    const formData = new FormData();
    formData.append('file', file);
    
    const parentId = document.getElementById('selected-folder-id').value;
    if (parentId) {
        formData.append('parent_id', parentId);
    }
    
    // Actualizar UI para mostrar progreso
    const uploadBtn = document.getElementById('uploadBtn');
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';
    
    try {
        const token = localStorage.getItem('auth_token');
        const response = await fetch('http://127.0.0.1:8000/api/upload-to-drive', {
            method: 'POST',
            body: formData,
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showFeedback('feedback', 'success', `Archivo "${file.name}" subido correctamente.`);
            fileInput.value = '';
            document.getElementById('file-preview').classList.add('hidden');
            
            // Actualizar lista si estamos en la misma carpeta
            if (currentFolderId === parentId) {
                setTimeout(() => listFiles(currentFolderId), 1000);
            }
        } else {
            showFeedback('feedback', 'error', `Error: ${result.error || 'No se pudo subir el archivo'}`);
        }
    } catch (error) {
        console.error("Error al subir:", error);
        showFeedback('feedback', 'error', 'Error de conexión al subir el archivo.');
    } finally {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Subir';
    }
});

// Añade esta función en la sección <script> de UploadFiles.blade.php
function getFileIconClass(mimeType, fileName) {
    // Si es una carpeta
    if (mimeType === 'application/vnd.google-apps.folder') {
        return 'fas fa-folder folder-icon';
    }
    
    // Determinar icono por extensión
    const extension = fileName.split('.').pop().toLowerCase();
    
    switch (extension) {
        case 'pdf': return 'fas fa-file-pdf file-pdf';
        case 'jpg': case 'jpeg': case 'png': case 'gif': return 'fas fa-file-image file-image';
        case 'doc': case 'docx': return 'fas fa-file-word file-word';
        case 'xls': case 'xlsx': return 'fas fa-file-excel file-excel';
        case 'ppt': case 'pptx': return 'fas fa-file-powerpoint file-ppt';
        case 'zip': case 'rar': return 'fas fa-file-archive file-archive';
        default: return 'fas fa-file file-icon';
    }
}
</script>
@endsection