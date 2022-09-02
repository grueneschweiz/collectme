import { computed } from "vue";
import router from "@/router";

const isLoginPage = computed<boolean>(() => {
  const path = router.currentRoute.value.path;
  console.log(path === "/login" || path === "/await-activation")
  return path === "/login" || path === "/await-activation";
});

export default isLoginPage;
