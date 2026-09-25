<script setup>
import { ref, computed } from "vue";
import { passwordScore, passwordProblem, SCORE_LABELS } from "../passwordStrength";

// Password input with show/hide and, for new passwords, a strength bar
const model = defineModel({ type: String, default: "" });
const props = defineProps({
  label: { type: String, required: true },
  autocomplete: { type: String, default: "current-password" },
  error: { type: String, default: "" },
  strength: { type: Boolean, default: false },
  username: { type: String, default: "" },
});

const show = ref(false);
const score = computed(() => passwordScore(model.value));
const hint = computed(() => (props.strength && model.value ? passwordProblem(model.value, props.username) : null));
</script>

<template>
  <label class="field">
    <span>{{ label }}</span>
    <div class="password-wrap">
      <input v-model="model" class="input" :class="{ invalid: error }" :type="show ? 'text' : 'password'"
        :autocomplete="autocomplete" maxlength="72" />
      <button type="button" class="btn btn-icon eye" :aria-label="show ? 'Parolni yashirish' : 'Parolni ko‘rsatish'"
        @click="show = !show">
        <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'" aria-hidden="true"></i>
      </button>
    </div>
    <template v-if="strength && model">
      <div class="meter" aria-hidden="true">
        <span v-for="i in 4" :key="i" :class="{ on: i <= score, [`s${score}`]: i <= score }"></span>
      </div>
      <span class="hint">{{ SCORE_LABELS[score] }}<template v-if="hint"> — {{ hint }}</template></span>
    </template>
    <span v-if="error" class="field-error">{{ error }}</span>
  </label>
</template>

<style scoped>
.password-wrap { position: relative; }
.password-wrap .input { padding-right: 44px; }
.eye { position: absolute; right: 4px; top: 50%; transform: translateY(-50%); border: none; }

.meter { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; }
.meter span { height: 4px; border-radius: 2px; background: var(--bg-hover); }
.meter .on.s1 { background: var(--danger); }
.meter .on.s2 { background: #ffb020; }
.meter .on.s3 { background: #c7e600; }
.meter .on.s4 { background: var(--success); }
</style>
