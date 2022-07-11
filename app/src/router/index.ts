import { createRouter, createWebHashHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import TheLogin from "@/components/specific/home/TheLogin.vue";
import TheObjectiveSetter from "@/components/specific/home/TheObjectiveSetter/TheObjectiveSetter.vue";
import TheSignatureAdder from "@/components/specific/home/TheSignatureAdder.vue";
import TheActivationAwaitor from "@/components/specific/home/TheActivationAwaitor.vue";

const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
      children: [
        {
          path: 'login',
          component: TheLogin
        },
        {
          path: 'await-activation',
          component: TheActivationAwaitor
        },
        {
          path: '/home/set-goal',
          component: TheObjectiveSetter
        },
        {
          path: '/home/enter-signatures',
          component: TheSignatureAdder
        }
      ],
    },
  ]
})

export default router
