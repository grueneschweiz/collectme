import { computed } from "vue";
import router from "@/router";

const isLoginPage = computed<boolean>(() => {
  const path = router.currentRoute.value.path;
  return path === "/login" || path === "/await-activation";
});

export default isLoginPage;
