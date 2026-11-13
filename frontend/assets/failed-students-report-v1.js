import {
  aH as api,
  aB as toast,
  aJ as nextTick,
  d as defineComponent,
  l as ref,
  G as watch,
  cd as onMounted,
  s as unref,
  q as createVNode,
  n as h,
  w as withCtx,
  F as Fragment,
  I as createTextVNode,
  y as toDisplayString,
  L as VCard,
  ay as VCardText,
  W as VBtn,
  A as VIcon,
} from "./index-8c561bc9.js";
import { V as VRow, a as VCol } from "./VRow-d41af6d5.js";
import { _ as AppAutocomplete } from "./AppAutocomplete.vue_vue_type_script_setup_true_lang-9b45fbe9.js";
import { _ as AppSelect } from "./AppSelect.vue_vue_type_script_setup_true_lang-2cb5367b.js";
import { _ as AppTextField } from "./AppTextField.vue_vue_type_script_setup_true_lang-939503b0.js";
import { V as VDataTable } from "./VDataTable-07129cea.js";
import { V as VChip } from "./VChip-14790242.js";
import { V as VAlert } from "./VAlert-48fed47d.js";
import { V as VSkeletonLoader } from "./VSkeletonLoader-879d1a83.js";
import { d as downloadFile } from "./file-utils-7ff42152.js";
import { b as blockRoute } from "./route-block-83d24a4e.js";

class FailedStudentsService {
  static buildQuery(params) {
    const qs = new URLSearchParams();
    Object.entries(params || {}).forEach(([k, v]) => {
      if (v !== null && v !== undefined && v !== "") {
        qs.append(k, String(v));
      }
    });
    const str = qs.toString();
    return str ? `?${str}` : "";
  }

  static async getFilters(params) {
    return await api.get(`/tenant/failed_students/filters${this.buildQuery(params)}`);
  }

  static async getList(payload) {
    return await api.post("/tenant/failed_students/list", payload);
  }

  static async download(type, params) {
    return await api.getBlob(`/tenant/export/execute/failed-students/${type}${this.buildQuery(params)}`);
  }
}

const HEADERS = [
  { title: "N°", key: "index", align: "center", sortable: false, width: "60px" },
  { title: "D.N.I.", key: "document_number", align: "center", sortable: false },
  { title: "Estudiante", key: "student_name", sortable: true },
  { title: "Programa de Estudios", key: "study_program_name", sortable: true },
  { title: "Ciclo", key: "cycle_name", align: "center", sortable: true },
  { title: "Sec. / Turno", key: "section_shift", sortable: false },
  { title: "Unidad Didáctica", key: "course_name", sortable: true },
  { title: "Docente", key: "teacher_name", sortable: true },
  { title: "Nota", key: "score", align: "center", sortable: true },
  { title: "Condición", key: "status", align: "center", sortable: false },
];

const FailedStudentsReport = defineComponent({
  __name: "failed-students",
  setup() {
    const initLoading = ref(true);
    const loading = ref(false);
    const exporting = ref(null);

    const periods = ref([]);
    const studyPrograms = ref([]);
    const cycles = ref([]);
    const classrooms = ref([]);

    const periodId = ref(null);
    const studyProgramId = ref(null);
    const cycleId = ref(null);
    const classroomId = ref(null);
    const search = ref("");

    const summary = ref({
      total_students: 0,
      total_approved: 0,
      total_failed: 0,
      failure_rate: 0,
      total_classrooms: 0,
    });
    const students = ref([]);

    let ready = false;
    let listRequestSeq = 0;
    let searchTimer = null;

    const currentPayload = (includeSearch = true) => ({
      period_id: periodId.value || null,
      study_program_id: studyProgramId.value || null,
      cycle_id: cycleId.value || null,
      classroom_id: classroomId.value || null,
      ...(includeSearch && search.value && search.value.trim() ? { search: search.value.trim() } : {}),
    });

    const loadFilters = async (preservePeriod = false) => {
      try {
        const res = await FailedStudentsService.getFilters({
          period_id: preservePeriod ? periodId.value : null,
          study_program_id: studyProgramId.value,
          cycle_id: cycleId.value,
        });
        const data = res.data || {};
        periods.value = data.periods || [];
        studyPrograms.value = data.study_programs || [];
        cycles.value = data.cycles || [];
        classrooms.value = data.classrooms || [];
        if (!preservePeriod && !periodId.value) {
          periodId.value = data.current_period_id || (periods.value[0]?.id ?? null);
        }
      } catch (err) {
        toast.error(err);
      }
    };

    const loadReport = async () => {
      const seq = ++listRequestSeq;
      loading.value = true;
      try {
        const res = await FailedStudentsService.getList(currentPayload(true));
        if (seq !== listRequestSeq) return;
        const data = res.data || {};
        summary.value = data.summary || {
          total_students: 0,
          total_approved: 0,
          total_failed: 0,
          failure_rate: 0,
          total_classrooms: 0,
        };
        students.value = data.students || [];
      } catch (err) {
        if (seq !== listRequestSeq) return;
        toast.error(err);
        students.value = [];
      } finally {
        if (seq === listRequestSeq) {
          loading.value = false;
        }
      }
    };

    const refreshClassroomsAndList = async () => {
      if (!ready) return;
      classroomId.value = null;
      await loadFilters(true);
      await loadReport();
    };

    const resetFilters = async () => {
      if (searchTimer) {
        clearTimeout(searchTimer);
        searchTimer = null;
      }
      ready = false;
      studyProgramId.value = null;
      cycleId.value = null;
      classroomId.value = null;
      search.value = "";
      periodId.value = null;
      await loadFilters(false);
      await nextTick();
      ready = true;
      await loadReport();
    };

    const handleExport = async (type) => {
      if (exporting.value !== null) return;
      exporting.value = type;
      try {
        const res = await FailedStudentsService.download(type, currentPayload(true));
        const ct = (res.headers?.["content-type"] || "").toLowerCase();
        if (ct.includes("application/json")) {
          const text = await res.data.text();
          const parsed = JSON.parse(text);
          throw new Error(parsed.message || "Error al generar el reporte");
        }
        downloadFile(res);
        toast.success(type === "xlsx" ? "Reporte Excel descargado correctamente" : "Reporte PDF descargado correctamente");
      } catch (err) {
        let msg = err?.message || err || "Error al descargar el reporte";
        if (err?.response?.data instanceof Blob) {
          try {
            const text = await err.response.data.text();
            const parsed = JSON.parse(text);
            if (parsed?.message) msg = parsed.message;
          } catch {}
        }
        toast.error(msg);
      } finally {
        exporting.value = null;
      }
    };

    watch([periodId, studyProgramId, cycleId], () => {
      if (ready) {
        refreshClassroomsAndList();
      }
    });

    watch(classroomId, () => {
      if (ready) {
        loadReport();
      }
    });

    watch(search, () => {
      if (!ready) return;
      if (searchTimer) clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        loadReport();
      }, 350);
    });

    onMounted(async () => {
      initLoading.value = true;
      await loadFilters(false);
      await nextTick();
      ready = true;
      initLoading.value = false;
      await loadReport();
    });

    const renderKpiCard = (title, value, subtitle, icon, colorClass, iconColor) =>
      createVNode(
        VCol,
        { cols: "12", sm: "6", md: "3" },
        {
          default: withCtx(() => [
            createVNode(VCard, { class: "h-100" }, {
              default: withCtx(() => [
                createVNode(VCardText, { class: "d-flex align-center justify-space-between pa-4" }, {
                  default: withCtx(() => [
                    h("div", null, [
                      h("span", { class: "text-body-2 text-medium-emphasis d-block mb-1" }, title),
                      h("div", { class: `text-h4 font-weight-bold ${colorClass}` }, toDisplayString(value)),
                      subtitle
                        ? h("span", { class: "text-caption text-medium-emphasis" }, toDisplayString(subtitle))
                        : null,
                    ]),
                    h(
                      "div",
                      {
                        class: `pa-3 rounded-lg bg-${iconColor}-lighten-5 d-flex align-center justify-center`,
                        style: { backgroundColor: "rgba(var(--v-theme-" + iconColor + "), 0.12)" },
                      },
                      [createVNode(VIcon, { icon, color: iconColor, size: "26" })]
                    ),
                  ]),
                }),
              ]),
            }),
          ]),
        }
      );

    return () =>
      createVNode("div", null, [
        // Header + botones Excel y PDF
        h("div", { class: "d-flex flex-column flex-sm-row justify-space-between align-start align-sm-center gap-4 mb-6" }, [
          h("div", null, [
            h("h1", { class: "text-h4 font-weight-bold mb-1" }, "Reporte de Estudiantes No Aprobados"),
            h(
              "p",
              { class: "text-body-2 text-medium-emphasis mb-0" },
              "Seguimiento de estudiantes desaprobados al finalizar cada unidad didáctica para planes de recuperación académica."
            ),
          ]),
          h("div", { class: "d-flex flex-wrap gap-2" }, [
            createVNode(VBtn, {
              class: "text-none",
              text: "Descargar Excel",
              color: "success",
              variant: "tonal",
              "prepend-icon": "mdi-file-excel",
              loading: unref(exporting) === "xlsx",
              disabled: unref(initLoading) || unref(loading) || unref(exporting) !== null,
              onClick: () => handleExport("xlsx"),
            }),
            createVNode(VBtn, {
              class: "text-none",
              text: "Descargar PDF",
              color: "error",
              variant: "tonal",
              "prepend-icon": "mdi-file-pdf-box",
              loading: unref(exporting) === "pdf",
              disabled: unref(initLoading) || unref(loading) || unref(exporting) !== null,
              onClick: () => handleExport("pdf"),
            }),
          ]),
        ]),

        unref(initLoading)
          ? createVNode(unref(VSkeletonLoader), { class: "w-100 mb-6", type: "article, table" })
          : createVNode(Fragment, { key: 1 }, [
              // Tarjetas KPI
              createVNode(VRow, { class: "mb-4" }, {
                default: withCtx(() => [
                  renderKpiCard(
                    "Total Evaluados",
                    unref(summary).total_students ?? 0,
                    `${unref(summary).total_classrooms ?? 0} unidades didácticas`,
                    "tabler-users",
                    "text-high-emphasis",
                    "primary"
                  ),
                  renderKpiCard(
                    "Aprobados (≥ 13)",
                    unref(summary).total_approved ?? 0,
                    "Cumplen nota mínima",
                    "tabler-circle-check",
                    "text-success",
                    "success"
                  ),
                  renderKpiCard(
                    "No Aprobados (< 13)",
                    unref(summary).total_failed ?? 0,
                    "Requieren recuperación",
                    "tabler-user-x",
                    "text-error",
                    "error"
                  ),
                  renderKpiCard(
                    "Tasa de Desaprobación",
                    `${unref(summary).failure_rate ?? 0}%`,
                    "Sobre estudiantes evaluados",
                    "tabler-chart-pie",
                    (unref(summary).failure_rate ?? 0) > 0 ? "text-warning" : "text-success",
                    "warning"
                  ),
                ]),
              }),

              // Tarjeta de Filtros
              createVNode(VCard, { class: "mb-6" }, {
                default: withCtx(() => [
                  createVNode(VCardText, null, {
                    default: withCtx(() => [
                      createVNode(VRow, null, {
                        default: withCtx(() => [
                          createVNode(VCol, { cols: "12", sm: "6", md: "2" }, {
                            default: withCtx(() => [
                              createVNode(AppSelect, {
                                modelValue: unref(periodId),
                                "onUpdate:modelValue": (val) => {
                                  periodId.value = val;
                                },
                                items: unref(periods),
                                "item-title": "name",
                                "item-value": "id",
                                label: "Periodo académico",
                                density: "compact",
                                "hide-details": true,
                              }),
                            ]),
                          }),
                          createVNode(VCol, { cols: "12", sm: "6", md: "3" }, {
                            default: withCtx(() => [
                              createVNode(AppSelect, {
                                modelValue: unref(studyProgramId),
                                "onUpdate:modelValue": (val) => {
                                  studyProgramId.value = val ?? null;
                                },
                                items: unref(studyPrograms),
                                "item-title": "name",
                                "item-value": "id",
                                label: "Programa de estudios",
                                placeholder: "Todos",
                                density: "compact",
                                clearable: true,
                                "hide-details": true,
                              }),
                            ]),
                          }),
                          createVNode(VCol, { cols: "12", sm: "6", md: "2" }, {
                            default: withCtx(() => [
                              createVNode(AppSelect, {
                                modelValue: unref(cycleId),
                                "onUpdate:modelValue": (val) => {
                                  cycleId.value = val ?? null;
                                },
                                items: unref(cycles),
                                "item-title": "name",
                                "item-value": "id",
                                label: "Ciclo",
                                placeholder: "Todos",
                                density: "compact",
                                clearable: true,
                                "hide-details": true,
                              }),
                            ]),
                          }),
                          createVNode(VCol, { cols: "12", sm: "6", md: "5" }, {
                            default: withCtx(() => [
                              createVNode(AppAutocomplete, {
                                modelValue: unref(classroomId),
                                "onUpdate:modelValue": (val) => {
                                  classroomId.value = val ?? null;
                                },
                                items: unref(classrooms),
                                "item-title": "name",
                                "item-value": "id",
                                label: "Unidad didáctica",
                                placeholder: "Todas las unidades didácticas",
                                "no-data-text": "Sin unidades didácticas",
                                density: "compact",
                                clearable: true,
                                "hide-details": true,
                              }),
                            ]),
                          }),
                          createVNode(VCol, { cols: "12", md: "9" }, {
                            default: withCtx(() => [
                              createVNode(AppTextField, {
                                modelValue: unref(search),
                                "onUpdate:modelValue": (val) => {
                                  search.value = val ?? "";
                                },
                                label: "Búsqueda rápida",
                                placeholder: "Buscar por DNI, estudiante, unidad didáctica o docente...",
                                "prepend-inner-icon": "tabler-search",
                                density: "compact",
                                clearable: true,
                                "hide-details": true,
                              }),
                            ]),
                          }),
                          createVNode(VCol, { cols: "12", md: "3", class: "d-flex align-end" }, {
                            default: withCtx(() => [
                              createVNode(VBtn, {
                                class: "text-none w-100",
                                text: "Limpiar filtros",
                                color: "secondary",
                                variant: "tonal",
                                "prepend-icon": "tabler-filter-off",
                                onClick: resetFilters,
                              }),
                            ]),
                          }),
                        ]),
                      }),
                    ]),
                  }),
                ]),
              }),

              // Aviso informativo si el periodo/filtro aún no tiene notas o si 0 desaprobados
              !unref(loading) && unref(summary).total_students === 0
                ? createVNode(
                    VAlert,
                    { type: "info", variant: "tonal", class: "mb-4" },
                    {
                      default: withCtx(() => [
                        createTextVNode(
                          "Aún no hay unidades didácticas finalizadas o con calificaciones registradas para los filtros seleccionados."
                        ),
                      ]),
                    }
                  )
                : !unref(loading) && unref(summary).total_students > 0 && unref(summary).total_failed === 0
                  ? createVNode(
                      VAlert,
                      { type: "success", variant: "tonal", class: "mb-4" },
                      {
                        default: withCtx(() => [
                          createTextVNode(
                            "No se registran estudiantes desaprobados en las unidades didácticas evaluadas bajo los filtros actuales."
                          ),
                        ]),
                      }
                    )
                  : null,

              // Tabla de resultados
              createVNode(VCard, null, {
                default: withCtx(() => [
                  createVNode(
                    unref(VDataTable),
                    {
                      items: unref(students),
                      headers: HEADERS,
                      "items-per-page": 15,
                      loading: unref(loading),
                      "loading-text": "Cargando estudiantes...",
                      "no-data-text": "No se encontraron estudiantes no aprobados con los filtros seleccionados.",
                    },
                    {
                      "item.score": withCtx(({ item }) => [
                        createVNode(
                          unref(VChip),
                          { color: "error", variant: "tonal", size: "small", class: "font-weight-bold" },
                          { default: withCtx(() => [createTextVNode(toDisplayString(item.score))]) }
                        ),
                      ]),
                      "item.status": withCtx(({ item }) => [
                        createVNode(
                          unref(VChip),
                          { color: "error", variant: "flat", size: "small" },
                          { default: withCtx(() => [createTextVNode(toDisplayString(item.status || "DESAPROBADO"))]) }
                        ),
                      ]),
                    }
                  ),
                ]),
              }),
            ]),
      ]);
  },
});

typeof blockRoute === "function" && blockRoute(FailedStudentsReport);
export { FailedStudentsReport as default };
