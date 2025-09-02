<template>
    <Head title="Управление прокси" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Управление прокси
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Форма добавления прокси -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Добавить новые прокси</h3>
                        
                        <form @submit.prevent="submitProxies" class="space-y-4" enctype="multipart/form-data">
                            <div>
                                <label for="proxy_file" class="block text-sm font-medium text-gray-700">
                                    Файл с прокси (TXT)
                                </label>
                                <input 
                                    type="file"
                                    id="proxy_file"
                                    ref="proxyFileInput"
                                    @change="handleProxyFile"
                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                    accept=".txt"
                                    required
                                />
                                <p class="mt-1 text-sm text-gray-500">
                                    Загрузите TXT файл с прокси в формате: ip:port:login:password (по одному на строку)
                                </p>
                                <div v-if="form.proxy_file" class="mt-2 text-sm text-green-600">
                                    ✓ Файл выбран: {{ form.proxy_file.name }} ({{ formatFileSize(form.proxy_file.size) }})
                                </div>
                            </div>
                            
                            <button 
                                type="submit" 
                                :disabled="loading"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                            >
                                {{ loading ? 'Добавление...' : 'Добавить прокси' }}
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

                <!-- Список прокси -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Список прокси</h3>
                            <button 
                                @click="loadProxies"
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
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP:Порт</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Протокол</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Логин</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Аккаунтов</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Использований</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Последнее использование</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="proxy in proxies.data" :key="proxy.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ proxy.id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ proxy.ip }}:{{ proxy.port }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                                {{ proxy.protocol.toUpperCase() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ proxy.login || '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ proxy.telegram_accounts_count }}/{{ proxy.max_accounts }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ proxy.usage_count }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ proxy.last_used_at ? new Date(proxy.last_used_at).toLocaleString('ru-RU') : 'Никогда' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="{
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium': true,
                                                'bg-green-100 text-green-800': proxy.is_active,
                                                'bg-red-100 text-red-800': !proxy.is_active
                                            }">
                                                {{ proxy.is_active ? 'Активен' : 'Неактивен' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button 
                                                v-if="proxy.is_active"
                                                @click="deactivateProxy(proxy.id)"
                                                class="text-red-600 hover:text-red-900 mr-2"
                                            >
                                                Деактивировать
                                            </button>
                                            <button 
                                                v-else
                                                @click="activateProxy(proxy.id)"
                                                class="text-green-600 hover:text-green-900"
                                            >
                                                Активировать
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="proxies.data && proxies.data.length === 0">
                                        <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                                            Прокси не найдены
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Пагинация -->
                        <div v-if="proxies.last_page > 1" class="mt-6 flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <button
                                    @click="changePage(proxies.current_page - 1)"
                                    :disabled="!proxies.prev_page_url"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                                >
                                    Предыдущая
                                </button>
                                <button
                                    @click="changePage(proxies.current_page + 1)"
                                    :disabled="!proxies.next_page_url"
                                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                                >
                                    Следующая
                                </button>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Показано
                                        <span class="font-medium">{{ proxies.from || 0 }}</span>
                                        -
                                        <span class="font-medium">{{ proxies.to || 0 }}</span>
                                        из
                                        <span class="font-medium">{{ proxies.total }}</span>
                                        результатов
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <button
                                            @click="changePage(proxies.current_page - 1)"
                                            :disabled="!proxies.prev_page_url"
                                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                                        >
                                            ←
                                        </button>
                                        <template v-for="page in getVisiblePages()" :key="page">
                                            <button
                                                @click="changePage(page)"
                                                :class="{
                                                    'relative inline-flex items-center px-4 py-2 border text-sm font-medium': true,
                                                    'z-10 bg-indigo-50 border-indigo-500 text-indigo-600': page === proxies.current_page,
                                                    'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': page !== proxies.current_page
                                                }"
                                            >
                                                {{ page }}
                                            </button>
                                        </template>
                                        <button
                                            @click="changePage(proxies.current_page + 1)"
                                            :disabled="!proxies.next_page_url"
                                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                                        >
                                            →
                                        </button>
                                    </nav>
                                </div>
                            </div>
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
    proxy_file: null
});
const proxies = ref({
    data: [],
    current_page: 1,
    last_page: 1,
    total: 0,
    from: 0,
    to: 0,
    prev_page_url: null,
    next_page_url: null
});
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

const handleProxyFile = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.value.proxy_file = file;
    }
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const submitProxies = async () => {
    loading.value = true;
    
    try {
        if (!form.value.proxy_file) {
            showMessage('Пожалуйста, выберите файл с прокси', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('proxy_file', form.value.proxy_file);
        
        const response = await axios.post('/proxies', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        
        if (response.data.success) {
            showMessage(response.data.message, 'success');
            // Очищаем форму
            form.value.proxy_file = null;
            // Сбрасываем input файла
            if (document.getElementById('proxy_file')) {
                document.getElementById('proxy_file').value = '';
            }
            await loadProxies();
        }
    } catch (error) {
        showMessage(error.response?.data?.message || 'Произошла ошибка', 'error');
    } finally {
        loading.value = false;
    }
};

const loadProxies = async (page = 1) => {
    try {
        const response = await axios.get(`/proxies?page=${page}`);
        if (response.data.success) {
            proxies.value = response.data.proxies;
        }
    } catch (error) {
        showMessage('Ошибка при загрузке прокси', 'error');
    }
};

const changePage = (page) => {
    if (page >= 1 && page <= proxies.value.last_page) {
        loadProxies(page);
    }
};

const getVisiblePages = () => {
    const current = proxies.value.current_page;
    const last = proxies.value.last_page;
    const delta = 2;
    const range = [];
    const rangeWithDots = [];

    for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        range.push(i);
    }

    if (current - delta > 2) {
        rangeWithDots.push(1, '...');
    } else {
        rangeWithDots.push(1);
    }

    rangeWithDots.push(...range);

    if (current + delta < last - 1) {
        rangeWithDots.push('...', last);
    } else if (last > 1) {
        rangeWithDots.push(last);
    }

    return rangeWithDots.filter(page => page !== '...' || true);
};

const deactivateProxy = async (proxyId) => {
    if (!confirm('Вы уверены, что хотите деактивировать этот прокси?')) {
        return;
    }
    
    try {
        const response = await axios.patch(`/proxies/${proxyId}/deactivate`);
        if (response.data.success) {
            showMessage(response.data.message, 'success');
            await loadProxies(proxies.value.current_page);
        }
    } catch (error) {
        showMessage('Ошибка при деактивации прокси', 'error');
    }
};

const activateProxy = async (proxyId) => {
    if (!confirm('Вы уверены, что хотите активировать этот прокси?')) {
        return;
    }
    
    try {
        const response = await axios.patch(`/proxies/${proxyId}/activate`);
        if (response.data.success) {
            showMessage(response.data.message, 'success');
            await loadProxies(proxies.value.current_page);
        }
    } catch (error) {
        showMessage('Ошибка при активации прокси', 'error');
    }
};

onMounted(() => {
    loadProxies();
});
</script>
