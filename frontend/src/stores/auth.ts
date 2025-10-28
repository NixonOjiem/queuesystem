import { defineStore } from 'pinia';
import Cookies from 'js-cookie'; // <-- Import js-cookie

export const useAuthStore = defineStore('auth', {
  state: () => ({
    // 1. Initialize state from the cookies on app load
    // We parse the user object, or default to null
    user: Cookies.get('auth_user') ? JSON.parse(Cookies.get('auth_user')) : null,
    // The token is just a string, or null
    token: Cookies.get('auth_token') || null,
  }),
  getters: {
    // Getter works as-is
    isAuthenticated: (state) => !!state.token,
  },
  actions: {
    login(userData, token) {
      // 2. Update the Pinia state
      this.user = userData;
      this.token = token;

      // 3. Set the cookies to persist the login
      // { expires: 7 } = 7 days
      // { secure: true } = only transmit over HTTPS (recommended for production)
      Cookies.set('auth_token', token, { expires: 7, secure: true });
      Cookies.set('auth_user', JSON.stringify(userData), { expires: 7, secure: true });
    },
    logout() {
      // 4. Clear the Pinia state
      this.user = null;
      this.token = null;

      // 5. Remove the cookies
      Cookies.remove('auth_token');
      Cookies.remove('auth_user');
    },
  },
});
