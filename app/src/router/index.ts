import { createRouter, createWebHashHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import TheLogin from "@/components/specific/home/TheLogin.vue";
import TheObjectiveSetter from "@/components/specific/home/TheObjectiveSetter/TheObjectiveSetter.vue";

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
          path: '/home/set-goal',
          component: TheObjectiveSetter
        }
      ],
    },
  ]
})

export default router
