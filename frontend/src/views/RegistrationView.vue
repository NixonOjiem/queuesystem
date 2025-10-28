<template>
  <div class="min-h-screen flex items-center justify-center p-4 bg-gray-100">
    <div class="w-full max-w-md bg-white rounded-xl shadow-2xl p-6 md:p-8 border border-gray-100">
      <h1 class="text-3xl font-bold text-center text-indigo-700 mb-2">Register User (Vue Tester)</h1>
      <p class="text-sm text-center text-gray-500 mb-6">POST to <code class="text-xs text-pink-600">{{ API_URL }}</code>
      </p>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Name Input -->
        <div>
          <label for="name" :class="labelClasses">Name</label>
          <input type="text" id="name" name="name" placeholder="John Doe" v-model="formData.name" required
            :class="inputClasses" />
        </div>

        <!-- Email Input -->
        <div>
          <label for="email" :class="labelClasses">Email Address</label>
          <!-- Using v-model for two-way binding on formData.email -->
          <input type="email" id="email" name="email" placeholder="test@example.com" v-model="formData.email" required
            :class="inputClasses" />
        </div>

        <!-- Password Input -->
        <div>
          <label for="password" :class="labelClasses">Password (min 8 chars)</label>
          <input type="password" id="password" name="password" placeholder="********" v-model="formData.password"
            required :class="inputClasses" />
        </div>

        <!-- Password Confirmation Input -->
        <div>
          <label for="password_confirmation" :class="labelClasses">Confirm Password</label>
          <input type="password" id="password_confirmation" name="password_confirmation" placeholder="********"
            v-model="formData.password_confirmation" required :class="inputClasses" />
        </div>

        <!-- Submit Button -->
        <button type="submit" :disabled="loading" :class="buttonClasses">
          <template v-if="loading">
            <div class="flex items-center justify-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
              </svg>
              Sending Request...
            </div>
          </template>
          <template v-else>
            Register
          </template>
        </button>
      </form>

      <!-- Error Alert (User-friendly validation messages) -->
      <div v-if="hasErrors" class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg" role="alert">
        <p class="font-semibold">Registration Failed</p>
        <!-- Use v-for on the computed property formatErrorMessages -->
        <ul class="list-disc list-inside mt-1 text-sm">
          <li v-for="(msg, index) in formatErrorMessages" :key="index">{{ msg }}</li>
        </ul>
      </div>

      <!-- Response Area (For full JSON output) -->
      <div v-if="isResponseVisible" :class="responseContainerClasses">
        <h3 class="font-semibold mb-2" :class="responseTextClasses">{{ responseTitle }}</h3>
        <pre class="bg-white p-3 rounded-md text-xs overflow-x-auto text-gray-800 border border-gray-200">
    <!-- Displaying the API response data as JSON string -->
    {{ JSON.stringify(responseData, null, 2) }}
  </pre>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import router from '@/router';
import { ref, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

const API_URL = `${import.meta.env.VITE_API_URL}/register`;

interface FormData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

interface ApiResponse {
  status: number;
  isSuccess: boolean;
  data: any;
}

export default {
  // Composition API setup function
  setup() {
    const authStore = useAuthStore();
    const formData = ref<FormData>({
      name: 'John Tester',
      // Randomized email for easier unique validation testing
      email: `testuser${Math.floor(Math.random() * 10000)}@dev.com`,
      password: 'password123',
      password_confirmation: 'password123',
    });
    const loading = ref(false);
    const response = ref<ApiResponse | null>(null); // Full API response data
    const errors = ref<any>(null); // Specific validation errors map

    // Handles form submission and API call
    const handleSubmit = async () => {
      loading.value = true;
      response.value = null;
      errors.value = null;

      try {
        const res = await fetch(API_URL, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify(formData.value),
        });

        const data = await res.json();

        if (res.ok) {
          // Success (HTTP 201)
          response.value = {
            status: res.status,
            isSuccess: true,
            data: data,
          };
          authStore.login(data.user, data.token);
          router.push({ name: 'welcome' })
        } else {
          // Failure (HTTP 4xx or 5xx)
          if (res.status === 422 && data.errors) {
            // Laravel Validation Errors (422 Unprocessable Content)
            errors.value = data.errors;
          } else {
            // Other API errors (e.g., 500)
            errors.value = { general: [data.message || `An unknown server error occurred. Status: ${res.status}`] };
          }

          // Set non-success response state
          response.value = {
            status: res.status,
            isSuccess: false,
            data: data,
          };
        }

      } catch (error: any) {
        // Network failure or CORS issues
        errors.value = { network: [`Network error: Could not reach the API endpoint at ${API_URL}. Check if your Laravel server is running. (${error.message})`] };
      } finally {
        loading.value = false;
      }
    };

    // Helper to extract and flatten all error messages for display (Computed property)
    const formatErrorMessages = computed(() => {
      if (!errors.value) return [];
      let messages: string[] = [];
      for (const key in errors.value) {
        if (Array.isArray(errors.value[key])) {
          messages = messages.concat(errors.value[key]);
        }
      }
      return messages;
    });

    // Computed properties for UI state
    const hasErrors = computed(() => errors.value && Object.keys(errors.value).length > 0);
    const isResponseVisible = computed(() => !!response.value);
    const responseData = computed(() => response.value?.data || errors.value);

    const responseTitle = computed(() => response.value
      ? (response.value.isSuccess ? `Success! Status ${response.value.status} Created` : `Error! Status ${response.value.status}`)
      : 'API Response');

    const responseContainerClasses = computed(() => {
      if (!response.value) return 'mt-6 p-4 rounded-xl shadow-inner';
      return response.value.isSuccess
        ? 'mt-6 p-4 rounded-xl shadow-inner bg-green-50 border border-green-400 text-green-700'
        : 'mt-6 p-4 rounded-xl shadow-inner bg-red-50 border border-red-400 text-red-700';
    });

    const responseTextClasses = computed(() => response.value?.isSuccess ? 'text-green-800' : 'text-red-800');

    // Tailwind Class Constants
    const inputClasses = "w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out";
    const labelClasses = "block text-sm font-medium text-gray-700 mb-1";
    const buttonClasses = "w-full bg-indigo-600 text-white py-2.5 rounded-lg font-semibold hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-500 focus:ring-opacity-50 transition duration-150 ease-in-out shadow-md disabled:opacity-50 disabled:cursor-not-allowed";


    return {
      formData,
      loading,
      errors,
      API_URL,
      handleSubmit,
      formatErrorMessages,
      hasErrors,
      isResponseVisible,
      responseContainerClasses,
      responseTitle,
      responseTextClasses,
      inputClasses,
      labelClasses,
      buttonClasses,
      responseData,
    };
  }
}
</script>

<style lang="">
/* Styles for Tailwind classes are assumed to be loaded by the environment */
</style>
