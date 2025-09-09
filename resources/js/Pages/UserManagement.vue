<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';

defineProps({
    users: Array,
});

const showCreateModal = ref(false);
const showDeleteModal = ref(false);
const userToDelete = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
});

const createUser = () => {
    form.post(route('users.store'), {
        onSuccess: () => {
            form.reset();
            showCreateModal.value = false;
        },
    });
};

const confirmDelete = (user) => {
    userToDelete.value = user;
    showDeleteModal.value = true;
};

const deleteUser = () => {
    if (userToDelete.value) {
        form.delete(route('users.destroy', userToDelete.value.id), {
            onSuccess: () => {
                showDeleteModal.value = false;
                userToDelete.value = null;
            },
        });
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('ru-RU');
};
</script>

<template>
    <Head title="Управление пользователями" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Управление пользователями
                </h2>
                <PrimaryButton @click="showCreateModal = true">
                    Добавить пользователя
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Имя
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Email
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Дата создания
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Действия
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="user in users" :key="user.id">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                            {{ user.name }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {{ user.email }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {{ formatDate(user.created_at) }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                            <DangerButton
                                                @click="confirmDelete(user)"
                                                class="text-xs"
                                            >
                                                Удалить
                                            </DangerButton>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create User Modal -->
        <Modal :show="showCreateModal" @close="showCreateModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Добавить нового пользователя
                </h2>

                <form @submit.prevent="createUser" class="mt-6 space-y-6">
                    <div>
                        <InputLabel for="name" value="Имя" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Пароль" />
                        <TextInput
                            id="password"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>

                    <div class="flex justify-end space-x-3">
                        <SecondaryButton @click="showCreateModal = false">
                            Отмена
                        </SecondaryButton>
                        <PrimaryButton :disabled="form.processing">
                            Создать
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete User Modal -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Удалить пользователя
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Вы уверены, что хотите удалить пользователя 
                    <strong>{{ userToDelete?.name }}</strong>?
                    Это действие нельзя отменить.
                </p>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showDeleteModal = false">
                        Отмена
                    </SecondaryButton>
                    <DangerButton @click="deleteUser" :disabled="form.processing">
                        Удалить
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
