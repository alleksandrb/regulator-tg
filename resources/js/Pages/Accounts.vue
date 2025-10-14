<template>
    <Head title="Управление аккаунтами" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Управление Telegram аккаунтами
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Форма добавления аккаунта -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Добавить аккаунты из папки</h3>
                        
                        <form @submit.prevent="submitAccounts" class="space-y-4" enctype="multipart/form-data">
                            <div>
                                <label for="account_files" class="block text-sm font-medium text-gray-700">
                                    Файлы аккаунтов (папка)
                                </label>
                                <input 
                                    type="file"
                                    id="account_files"
                                    ref="accountFilesInput"
                                    @change="handleAccountFiles"
                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                    accept=".session,.json,.txt,.dat"
                                    multiple
                                    webkitdirectory
                                    required
                                />
                                <p class="mt-1 text-sm text-gray-500">
                                    Выберите папку с файлами аккаунтов. Для каждого аккаунта должны быть файлы с одинаковым именем, но разными расширениями (.session и .json)
                                </p>
                                <div v-if="accountPairs.length > 0" class="mt-3 space-y-2">
                                    <p class="text-sm font-medium text-green-600">
                                        ✓ Найдено {{ accountPairs.length }} пар(ы) файлов:
                                    </p>
                                    <div class="max-h-40 overflow-y-auto bg-gray-50 rounded-md p-3">
                                        <div v-for="pair in accountPairs" :key="pair.name" class="flex items-center justify-between py-1">
                                            <span class="text-sm text-gray-700">{{ pair.name }}</span>
                                            <span class="text-xs text-gray-500">{{ formatFileSize(pair.sessionFile.size + pair.jsonFile.size) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="invalidFiles.length > 0" class="mt-3">
                                    <p class="text-sm font-medium text-red-600">
                                        ⚠ Файлы без пары (будут пропущены):
                                    </p>
                                    <div class="max-h-32 overflow-y-auto bg-red-50 rounded-md p-2 mt-1">
                                        <div v-for="file in invalidFiles" :key="file.name" class="text-xs text-red-700">
                                            {{ file.name }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label for="proxy_file" class="block text-sm font-medium text-gray-700">
                                    Необязательно: файл с прокси (TXT)
                                </label>
                                <input 
                                    type="file"
                                    id="proxy_file"
                                    ref="proxyFileInput"
                                    @change="handleProxyFile"
                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                    accept=".txt"
                                />
                                <p class="mt-1 text-sm text-gray-500">
                                    Если приложить файл с прокси, аккаунты будут по порядку привязаны к ним. При нехватке — оставшиеся возьмут наименее загруженные прокси.
                                </p>
                                <div v-if="form.proxy_file" class="mt-2 text-sm text-green-600">
                                    ✓ Файл выбран: {{ form.proxy_file.name }} ({{ formatFileSize(form.proxy_file.size) }})
                                </div>
                            </div>
                            
                            <button 
                                type="submit" 
                                :disabled="loading || accountPairs.length === 0"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                            >
                                {{ loading ? 'Добавление...' : (accountPairs.length > 0 ? `Добавить ${accountPairs.length} аккаунт(ов)` : 'Выберите файлы') }}
                            </button>
                        </form>

                        <!-- Сообщения -->
                        <div v-if="message" :class="{
                            'mt-4 p-4 rounded-md': true,
                            'bg-green-50 text-green-800 border border-green-200': messageType === 'success',
                            'bg-red-50 text-red-800 border border-red-200': messageType === 'error'
                        }">
                            {{ message }}
                        </div>
                    </div>
                </div>

                <!-- Список аккаунтов -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Список аккаунтов</h3>
                            <button 
                                @click="loadAccounts"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Обновить
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Прокси</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Использований</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Последнее использование</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="account in accounts" :key="account.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ account.id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">
                                            {{ account.user_id || '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ account.proxy_id || '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ account.usage_count }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ account.last_used_at ? new Date(account.last_used_at).toLocaleString('ru-RU') : 'Никогда' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="{
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium': true,
                                                'bg-green-100 text-green-800': account.is_active,
                                                'bg-red-100 text-red-800': !account.is_active
                                            }">
                                                {{ account.is_active ? 'Активен' : 'Неактивен' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button 
                                                v-if="account.is_active"
                                                @click="deactivateAccount(account.id)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Деактивировать
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="accounts.length === 0">
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                            Аккаунты не найдены
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const form = ref({
    files: [],
    proxy_file: null
});
const accounts = ref([]);
const accountPairs = ref([]);
const invalidFiles = ref([]);
const loading = ref(false);
const message = ref('');
const messageType = ref('');

const showMessage = (msg, type) => {
    message.value = msg;
    messageType.value = type;
    setTimeout(() => {
        message.value = '';
    }, 5000);
};

const handleAccountFiles = (event) => {
    const files = Array.from(event.target.files);
    form.value.files = files;
    
    // Группируем файлы по имени (без расширения)
    const fileGroups = {};
    const orphanFiles = [];
    
    files.forEach(file => {
        const nameWithoutExt = file.name.substring(0, file.name.lastIndexOf('.'));
        const extension = file.name.substring(file.name.lastIndexOf('.'));
        
        if (!fileGroups[nameWithoutExt]) {
            fileGroups[nameWithoutExt] = {};
        }
        
        if (extension === '.session') {
            fileGroups[nameWithoutExt].session = file;
        } else if (extension === '.json') {
            fileGroups[nameWithoutExt].json = file;
        } else {
            orphanFiles.push(file);
        }
    });
    
    // Находим полные пары (session + json)
    const pairs = [];
    const incomplete = [];
    
    Object.entries(fileGroups).forEach(([name, group]) => {
        if (group.session && group.json) {
            pairs.push({
                name: name,
                sessionFile: group.session,
                jsonFile: group.json
            });
        } else {
            if (group.session) incomplete.push(group.session);
            if (group.json) incomplete.push(group.json);
        }
    });
    
    accountPairs.value = pairs;
    invalidFiles.value = [...incomplete, ...orphanFiles];
};

const handleProxyFile = (event) => {
    const file = event.target.files[0];
    form.value.proxy_file = file || null;
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const submitAccounts = async () => {
    loading.value = true;
    
    try {
        if (accountPairs.value.length === 0) {
            showMessage('Пожалуйста, выберите папку с файлами аккаунтов', 'error');
            return;
        }
        
        const formData = new FormData();
        
        // Добавляем каждую пару файлов
        accountPairs.value.forEach((pair, index) => {
            formData.append(`accounts[${index}][session_data]`, pair.sessionFile);
            formData.append(`accounts[${index}][json_data]`, pair.jsonFile);
            formData.append(`accounts[${index}][name]`, pair.name);
        });
        if (form.value.proxy_file) {
            formData.append('proxy_file', form.value.proxy_file);
        }
        
        const response = await axios.post('/accounts/bulk', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        
        if (response.data.success) {
            let message = `Успешно добавлено ${response.data.created_count} аккаунт(ов)`;
            if (response.data.skipped_count > 0) {
                message += `. Пропущено дубликатов: ${response.data.skipped_count}`;
            }
            if (response.data.failed_count > 0) {
                message += `. Ошибок: ${response.data.failed_count}`;
            }
            showMessage(message, 'success');
            
            if (response.data.failed_count > 0 || response.data.skipped_count > 0) {
                console.log('Подробности ошибок:', response.data.errors);
            }
            // Очищаем форму
            form.value.files = [];
            form.value.proxy_file = null;
            accountPairs.value = [];
            invalidFiles.value = [];
            // Сбрасываем input файлы
            if (document.getElementById('account_files')) {
                document.getElementById('account_files').value = '';
            }
            if (document.getElementById('proxy_file')) {
                document.getElementById('proxy_file').value = '';
            }
            await loadAccounts();
        }
    } catch (error) {
        showMessage(error.response?.data?.message || 'Произошла ошибка', 'error');
    } finally {
        loading.value = false;
    }
};

const loadAccounts = async () => {
    try {
        const response = await axios.get('/accounts');
        if (response.data.success) {
            accounts.value = response.data.accounts;
        }
    } catch (error) {
        showMessage('Ошибка при загрузке аккаунтов', 'error');
    }
};

const deactivateAccount = async (accountId) => {
    if (!confirm('Вы уверены, что хотите деактивировать этот аккаунт?')) {
        return;
    }
    
    try {
        const response = await axios.patch(`/accounts/${accountId}/deactivate`);
        if (response.data.success) {
            showMessage(response.data.message, 'success');
            await loadAccounts();
        }
    } catch (error) {
        showMessage('Ошибка при деактивации аккаунта', 'error');
    }
};

onMounted(() => {
    loadAccounts();
});
</script>
