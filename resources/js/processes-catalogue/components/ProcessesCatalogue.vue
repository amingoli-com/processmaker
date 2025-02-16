<template>
  <div>
    <breadcrumbs
      ref="breadcrumb"
      :category="category ? category.name : ''"
      :process="selectedProcess ? selectedProcess.name : ''"
      :template="guidedTemplates ? 'Guided Templates' : ''"
    />
    <b-row>
      <b-col cols="2">
        <span class="pl-3 menu-title"> {{ $t('Process Browser') }} </span>
        <MenuCatologue
          ref="categoryList"
          title="Available Processes"
          preicon="fas fa-play-circle"
          class="mt-3"
          show-bookmark="true"
          :category-count="categoryCount"
          :data="listCategories"
          :from-process-list="fromProcessList"
          :select="selectCategorie"
          :filter-categories="filterCategories"
          :permission="permission"
          @wizardLinkSelect="wizardTemplatesSelected"
          @addCategories="addCategories"
        />
      </b-col>
      <b-col cols="10">
        <div
          v-if="!showWizardTemplates && !showCardProcesses && !showProcess"
          class="d-flex justify-content-center py-5"
        >
          <CatalogueEmpty />
        </div>
        <div v-else>
          <CardProcess
            v-if="showCardProcesses && !showWizardTemplates && !showProcess"
            :key="key"
            :category="category"
            @openProcess="openProcess"
            @wizardLinkSelect="wizardTemplatesSelected"
          />
          <ProcessInfo
            v-if="showProcess && !showWizardTemplates && !showCardProcesses"
            :process="selectedProcess"
            :current-user-id="currentUserId"
            :current-user="currentUser"
            :permission="permission"
            :is-documenter-installed="isDocumenterInstalled"
            @goBackCategory="returnedFromInfo"
          />
          <wizard-templates
            v-if="showWizardTemplates"
            :template="guidedTemplates"
          />
        </div>
      </b-col>
    </b-row>
  </div>
</template>

<script>
import ProcessInfo from "./ProcessInfo.vue";
import MenuCatologue from "./menuCatologue.vue";
import CatalogueEmpty from "./CatalogueEmpty.vue";
import CardProcess from "./CardProcess.vue";
import Breadcrumbs from "./Breadcrumbs.vue";
import WizardTemplates from "./WizardTemplates.vue";

export default {
  components: {
    MenuCatologue, CatalogueEmpty, Breadcrumbs, CardProcess, WizardTemplates, ProcessInfo,
  },
  props: ["permission", "isDocumenterInstalled", "currentUserId", "process", "currentUser"],
  data() {
    return {
      listCategories: [],
      defaultOptions: [
        {
          id: -1,
          name: this.$t("All Processes"),
          status: "ACTIVE",
        },
        {
          id: 0,
          name: this.$t("My Bookmarks"),
          status: "ACTIVE",
        },
      ],
      fields: [],
      wizardTemplates: [],
      showWizardTemplates: false,
      showCardProcesses: false,
      showProcess: false,
      category: null,
      selectedProcess: null,
      guidedTemplates: false,
      numCategories: 15,
      page: 1,
      key: 0,
      totalPages: 1,
      filter: "",
      markCategory: false,
      fromProcessList: false,
      categoryCount: 0,
    };
  },
  computed: {
    hasGuidedTemplateParams() {
      return window.location.search.includes("?guided_templates=true");
    },
  },
  mounted() {
    if (this.hasGuidedTemplateParams) {
      // Loaded from URL with guided template parameters to show guided templates
      // Dynamically load the component
      this.wizardTemplatesSelected(true);
    } else {
      this.getCategories();
      setTimeout(() => {
        this.checkSelectedProcess();
      }, 500);
    }
  },
  methods: {
    /**
     * Add new page of categories
     */
    addCategories() {
      this.page += 1;
      this.getCategories();
    },
    /**
     * Filter categories
     */
    filterCategories(filter = "") {
      this.page = 1;
      this.listCategories = [];
      this.filter = filter;
      this.getCategories();
    },
    /**
     * Get list of categories
     */
    getCategories() {
      if (this.page <= this.totalPages) {
        ProcessMaker.apiClient
          .get("process_bookmarks/categories?status=active"
            + "&order_by=name"
            + "&order_direction=asc"
            + `&page=${this.page}`
            + `&per_page=${this.numCategories}`
            + `&filter=${this.filter}`)
          .then((response) => {
            if(!this.checkDefaultOptions()) {
              this.listCategories = [...this.defaultOptions, ...this.listCategories];
            }
            this.listCategories = [...this.listCategories, ...response.data.data];
            this.totalPages = response.data.meta.total_pages !== 0 ? response.data.meta.total_pages : 1;
            this.categoryCount = response.data.meta.total;
            if (this.markCategory) {
              const indexCategory = this.listCategories.findIndex((category) => category.name === this.category.name);
              this.$refs.categoryList.markCategory(this.listCategories[indexCategory]);
              this.markCategory = false;
            }
          });
      }
    },
    /**
     * Check if listCatefgories have the default options
     */
    checkDefaultOptions() {
      return this.defaultOptions.every(v => this.listCategories.includes(v));
    },
    /**
     * Check if there is a pre-selected process
     */
    checkSelectedProcess() {
      if (this.process) {
        this.openProcess(this.process);
        this.fromProcessList = true;
        const categories = this.process.process_category_id;
        const categoryId = typeof categories === "string" ? categories.split(",")[0] : categories;
        ProcessMaker.apiClient
          .get(`process_bookmarks/${categoryId}`)
          .then((response) => {
            this.category = response.data;
            this.markCategory = true;
            this.filterCategories(this.category.name);
          });
      }
    },
    /**
     * Select a category and show display
     */
    selectCategorie(value) {
      this.key += 1;
      this.category = value;
      this.selectedProcess = null;
      this.showCardProcesses = true;
      this.guidedTemplates = false;
      this.showWizardTemplates = false;

      // Remove guided_templates and template parameters from the URL
      if (value !== undefined) {
        const url = new URL(window.location.href);
        url.searchParams.delete("guided_templates");
        url.searchParams.delete("template");
        history.pushState(null, "", url); // Update the URL without triggering a page reload
      }

      this.showProcess = false;
    },
    /**
     * Select a wizard templates and show display
     */
    wizardTemplatesSelected(hasUrlParams = false) {
      if (!hasUrlParams) {
        // Add the params if the guided template link was selected
        const url = new URL(window.location.href);
        if (!url.search.includes("?guided_templates=true")) {
          url.searchParams.append("guided_templates", true);
          history.pushState(null, "", url); // Update the URL without triggering a page reload
        }
      }

      // Update state variables
      this.showWizardTemplates = true;
      this.guidedTemplates = true;
      this.showCardProcesses = false;
      this.showProcess = false;
      this.selectedProcess = null;
      this.category = null;
    },
    /**
     * Select a process and show display
     */
    openProcess(process) {
      this.showCardProcesses = false;
      this.guidedTemplates = false;
      this.showProcess = true;
      this.selectedProcess = process;
    },
    /**
     * Return a process cards from process info
     */
    returnedFromInfo() {
      this.selectCategorie(this.category);
    },
  },
};
</script>

<style scoped>
.menu-title {
  color: #556271;
  font-size: 22px;
  font-style: normal;
  font-weight: 600;
  line-height: 46.08px;
  letter-spacing: -0.44px;
}
</style>
