<div x-data="cloudinaryUploadComponent()" 
     class="border border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center bg-gray-50 dark:bg-gray-800">
    
    <!-- File Input -->
    <input type="file" 
           x-ref="fileInput" 
           accept="image/*" 
           class="block w-full text-sm text-gray-900 dark:text-gray-100 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900 dark:file:text-blue-300 dark:hover:file:bg-blue-800 mb-4">
    
    <!-- Upload Button -->
    <button type="button" 
            @click="uploadToCloudinary()" 
            :disabled="uploading"
            :class="uploading ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-600 dark:hover:bg-blue-500 hover:shadow-lg transform hover:scale-105'"
            class="inline-flex items-center justify-center px-8 py-4 bg-blue-500 dark:bg-blue-600 !text-white font-semibold rounded-xl shadow-md transition-all duration-200 min-w-[240px] text-lg">
        <span x-show="!uploading" class="flex items-center !text-white">
            <span class="text-2xl mr-3">📤</span>
            <span class="!text-white">Upload to Cloudinary</span>
        </span>
        <span x-show="uploading" class="flex items-center !text-white">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 !text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-lg !text-white">Uploading...</span>
        </span>
    </button>
    
    <!-- Status Messages -->
    <div x-show="message" 
         x-text="message" 
         :class="messageType === 'success' ? 'text-green-600 dark:text-green-400' : messageType === 'error' ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400'"
         class="mt-4 font-medium"></div>
    
    <!-- Image Preview -->
    <div x-show="previewUrl" class="mt-4">
        <img :src="previewUrl" 
             class="mx-auto max-w-xs max-h-48 rounded-lg shadow-md border border-gray-200 dark:border-gray-600">
    </div>
</div>

<script>
if (typeof window.cloudinaryUploadComponent === 'undefined') {
    window.cloudinaryUploadComponent = function() {
        return {
            uploading: false,
            message: '',
            messageType: '',
            previewUrl: '',
            
            uploadToCloudinary() {
                const file = this.$refs.fileInput.files[0];
                
                if (!file) {
                    this.showMessage('⚠️ Please select a file first', 'error');
                    return;
                }
                
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    this.showMessage('❌ Please select an image file', 'error');
                    return;
                }
                
                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    this.showMessage('❌ File too large. Max 2MB allowed', 'error');
                    return;
                }
                
                this.upload(file);
            },
            
            async upload(file) {
                this.uploading = true;
                this.showMessage('⏳ Uploading to Cloudinary...', 'info');
                
                try {
                    const formData = new FormData();
                    formData.append('image', file);
                    
                    console.log('=== UPLOAD REQUEST START ===');
                    console.log('File details:', {
                        name: file.name,
                        size: file.size,
                        type: file.type,
                        lastModified: file.lastModified
                    });
                    console.log('FormData entries:');
                    for (let pair of formData.entries()) {
                        console.log(pair[0] + ':', pair[1]);
                    }
                    
                    // Use API endpoint (no CSRF token required)
                    const response = await fetch('/api/upload-to-cloudinary', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    
                    console.log('Response status:', response.status);
                    console.log('Response status text:', response.statusText);
                    console.log('Response headers:', Object.fromEntries(response.headers.entries()));
                    
                    // Get the raw response text first
                    const responseText = await response.text();
                    console.log('Raw response text:', responseText);
                    
                    // Try to parse as JSON
                    let data;
                    try {
                        data = JSON.parse(responseText);
                        console.log('Parsed response data:', data);
                    } catch (parseError) {
                        console.error('Failed to parse JSON response:', parseError);
                        console.log('Response was not valid JSON');
                        this.showMessage('❌ Server returned invalid response: ' + responseText.substring(0, 100), 'error');
                        return;
                    }
                    
                    if (response.ok && data.success) {
                        console.log('Upload successful!');
                        this.handleSuccess(data.url);
                    } else {
                        console.error('Upload failed:', data);
                        this.showMessage('❌ ' + (data.error || data.message || 'Upload failed'), 'error');
                        if (data.debug) {
                            console.log('Debug info:', data.debug);
                        }
                    }
                    console.log('=== UPLOAD REQUEST END ===');
                } catch (error) {
                    console.error('Network/Exception error:', error);
                    this.showMessage('❌ Network error: ' + error.message, 'error');
                } finally {
                    this.uploading = false;
                }
            },
            
            handleSuccess(url) {
                // Update the image URL field with multiple fallback strategies
                const selectors = [
                    'input[name="image"]',
                    'input[id*="image"]',
                    'input[wire\\:model*="image"]',
                    'input[wire\\:model\\.defer*="image"]',
                    '[x-model*="image"]'
                ];
                
                let imageUrlField = null;
                for (const selector of selectors) {
                    imageUrlField = document.querySelector(selector);
                    if (imageUrlField) break;
                }
                
                if (imageUrlField) {
                    imageUrlField.value = url;
                    imageUrlField.dispatchEvent(new Event('input', { bubbles: true }));
                    imageUrlField.dispatchEvent(new Event('change', { bubbles: true }));
                    
                    // Try different Livewire integration methods
                    try {
                        // Livewire v3 style
                        if (typeof window.Livewire !== 'undefined' && window.Livewire.dispatch) {
                            window.Livewire.dispatch('refreshComponent');
                        }
                        // Livewire v2 style
                        else if (typeof window.Livewire !== 'undefined' && window.Livewire.emit) {
                            window.Livewire.emit('refreshComponent');
                        }
                        // Alpine/Livewire integration
                        else if (typeof this.$wire !== 'undefined') {
                            this.$wire.$refresh();
                        }
                        // Generic Livewire refresh attempt
                        else if (typeof window.Livewire !== 'undefined' && window.Livewire.rescan) {
                            window.Livewire.rescan();
                        }
                    } catch (livewireError) {
                        console.log('Livewire integration not available, field updated directly:', livewireError.message);
                    }
                } else {
                    console.warn('Could not find image input field to update');
                }
                
                // Show success and preview
                this.showMessage('✅ Upload successful!', 'success');
                this.previewUrl = url;
                
                // Clear file input
                this.$refs.fileInput.value = '';
            },
            
            showMessage(text, type) {
                this.message = text;
                this.messageType = type;
                
                // Auto-clear non-error messages after 5 seconds
                if (type !== 'error') {
                    setTimeout(() => {
                        if (this.messageType === type) {
                            this.message = '';
                            this.messageType = '';
                        }
                    }, 5000);
                }
            }
        }
    };
}
</script> 