<template>
  <div>
    <div class="">
      <div v-if="condensed">
        <label>{{ inputLabel ? inputLabel : "PMQL" }}</label>
        <mustache-helper />
        <i
          v-if="aiLoading"
          class="fa fa-spinner fa-spin pmql-icons"
          :style="styles?.icons"
        />
      </div>

      <div class="d-flex align-items-start">
        <div class="search-bar-buttons d-flex ml-md-0 flex-column flex-md-row">
          <slot name="left-buttons" />

          <pmql-input-filters
            v-if="showFilters"
            :type="searchType"
            :param-process="urlPmql ? '' : paramProcess"
            :param-status="urlPmql ? '' : paramStatus"
            :param-requester="urlPmql ? '' : paramRequester"
            :param-participants="urlPmql ? '' : paramParticipants"
            :param-request="urlPmql ? '' : paramRequest"
            :param-name="urlPmql ? '' : paramName"
            :param-projects="urlPmql ? '' : paramProjects"
            :param-project-members="urlPmql ? '' : paramProjectMembers"
            :param-project-categories="
              urlPmql ? '' : paramProjectCategories
            "
            :permission="permission"
            @filterspmqlchange="onFiltersPmqlChange"
          />
        </div>

        <div
          class="search-bar flex-grow w-100"
          :class="{ 'is-invalid': validations }"
          :style="styles?.container"
        >
          <div class="search-bar-container d-flex align-items-center">
            <i
              v-if="!aiLoading && !condensed"
              class="fa fa-search ml-3 pmql-icons"
              :style="styles?.icons"
            />
            <i
              v-if="aiLoading && !condensed"
              class="fa fa-spinner fa-spin ml-3 pmql-icons"
              :style="styles?.icons"
            />
            <b-tooltip
              v-if="showPmqlSection && !condensed"
              custom-class="pmql-tooltip"
              target="pmql-pill"
              triggers="hover"
              placement="bottom"
            >
              <div class="d-inline align-middle copy-container">
                <p
                  id="textToCopy"
                  class="d-inline"
                >
                  {{ pmql }}
                </p>
                <i
                  class="copy-icon fa fa-copy"
                  style="cursor: pointer"
                  @click="copyToClipboard"
                />
              </div>
            </b-tooltip>
            <textarea
              :id="inputId"
              ref="search_input"
              v-model="query"
              type="text"
              class="pmql-input"
              :class="{ 'overflow-auto': showScrollbars }"
              :aria-label="inputAriaLabel"
              :placeholder="placeholder || $t('Search here')"
              rows="1"
              :style="styles?.input"
              @input="onInput()"
              @keydown.enter.prevent
              @keyup.enter="runSearch()"
            />

            <div
              v-if="showAiIndicator && !condensed"
              class="separator align-items-center"
              :style="styles?.separators"
            />
            <span
              v-if="showAiIndicator && !condensed"
              class="badge badge-pill badge-success"
            >AI</span>

            <div
              v-if="showUsage && !condensed"
              class="separator align-items-center"
              :style="styles?.separators"
            />
            <label
              v-if="showUsage && !condensed"
              v-b-tooltip.hover
              class="badge badge-primary badge-pill usage-label"
              :title="usageText"
            >
              {{ usage.totalTokens }} tokens
              <i class="fa fa-info-circle ml-1 pmql-icons" />
            </label>

            <div
              v-if="!condensed"
              class="separator-transparent align-items-center"
              :style="styles?.separators"
            />
            <i
              v-if="!condensed"
              class="fa fa-times pl-1 pr-3 pmql-icons"
              role="button"
              :style="styles?.icons"
              @click="clearQuery"
            />
            <div
              v-if="showPmqlSection && !condensed"
              class="separator align-items-center"
              :style="styles?.separators"
            />
            <b-badge
              v-if="showPmqlSection && !condensed"
              id="pmql-pill"
              data-bs-toggle="tooltip"
              class="pmql-badge"
            >
              PMQL
            </b-badge>
          </div>
        </div>
        <div class="search-bar-buttons d-flex ml-md-0 flex-column flex-md-row">
          <slot name="right-buttons" />
        </div>
      </div>

      <div
        v-if="filterBadges.length > 0"
        class="selected-filters-bar d-flex pt-2"
      >
        <span
          v-for="filter in filterBadges"
          class="selected-filter-item d-flex align-items-center"
        >
          <span class="selected-filter-key mr-1">{{ $t(capitalizeString(filter[0])) }}<template v-if="!get(filter, '1.0.advanced_filter', false)">:</template></span>
          {{ filter[1][0].operator ?? '' }}
          <template v-if="filter[0] === 'Status'">
            <!-- translate status label -->
            {{ $t(filter[1][0].name) }}
          </template>
          <template v-else>
            {{ filter[1][0].name ? filter[1][0].name : filter[1][0].fullname }}
          </template>
          <span
            v-if="filter[1].length > 1"
            class="badge badge-pill ml-2 filter-counter"
          >
            +{{ filter[1].length - 1 }}
          </span>
          <i
            role="button"
            class="fa fa-times pl-2 pr-0"
            @click="removeFilter(filter)"
            v-if="!get(filter, '1.0.advanced_filter', false)"
          />
        </span>
      </div>
    </div>
  </div>
</template>
<script>
import { MustacheHelper } from "@processmaker/screen-builder";
import PmqlInputFilters from "./PmqlInputFilters.vue";
import advancedFilterStatusMixin from "../../common/advancedFilterStatusMixin";
import { get } from "lodash";

export default {
  components: { MustacheHelper, PmqlInputFilters },
  mixins: [advancedFilterStatusMixin],
  props: [
    "searchType",
    "value",
    "urlPmql",
    "filtersValue",
    "aiEnabled",
    "ariaLabel",
    "inputId",
    "validations",
    "styles",
    "condensed",
    "placeholder",
    "collectionId",
    "inputLabel",
    "showFilters",
    "hidePmqlSection",

    "paramProcess",
    "paramStatus",
    "paramRequester",
    "paramParticipants",
    "paramRequest",
    "paramProjects",
    "paramProjectMembers",
    "paramProjectCategories",
    "paramName",
    "permission",
    "updateQuery",
  ],
  data() {
    return {
      aiLoading: false,
      inputAriaLabel: "",
      showUsage: false,
      showAiIndicator: false,
      showFilterPopup: false,
      showScrollbars: false,
      pmql: "",
      filtersPmql: "",
      selectedFilters: [],
      query: "",
      textAreaLines: 4,
      usage: {
        completionTokens: 0,
        promptTokens: 0,
        totalTokens: 0,
      },
      get,
    };
  },

  computed: {
    filterBadges() {
      if (!this.showFilters) {
        return [];
      }
      return [...this.selectedFilters, ...this.formatAdvancedFilterForBadges];
    },
    showPmqlSection() {
      return (
        !this.hidePmqlSection
        && this.pmql
        && this.pmql.isPMQL()
        && !this.query.isPMQL()
      );
    },
    usageText() {
      const promptTokens = `Prompt tokens: ${this.usage.promptTokens}`;
      const completionTokens = ` - Completion tokens: ${this.usage.completionTokens}`;
      const totalTokens = ` - Total: ${this.usage.totalTokens} tokens`;
      return promptTokens + completionTokens + totalTokens;
    },
    aiEnabledLocal() {
      if (
        !window.ProcessMaker.openAi.enabled
        || window.ProcessMaker.openAi.enabled === ""
      ) {
        return false;
      }
      if (!this.searchType || this.searchType === "") {
        return false;
      }

      return this.aiEnabled;
    },
  },

  watch: {
    query() {
      this.calcInputHeight();
    },
    value() {
      if (this.updateQuery && this.query !== this.value) {
        this.query = this.value || "";
      } else if (!this.query || this.query === "") {
        this.query = this.value;
      }
    },
  },
  mounted() {
    this.query = this.urlPmql ? this.urlPmql : this.value;
    this.filtersPmql = this.filtersValue;
    this.inputAriaLabel = this.ariaLabel;

    this.$root.$on("bv::collapse::state", (collapseId, isJustShown) => {
      this.query = this.value;
      this.pmql = this.value;
      this.calcInputHeight();
    });

    if (this.urlPmql) {
      this.$emit("submit", this.query);
      this.$emit("input", this.query);
    }
  },

  methods: {
    onFiltersPmqlChange(value) {
      this.filtersPmql = value[0];
      this.selectedFilters = value[1];
      this.$emit("filterspmqlchange", [this.filtersPmql, this.selectedFilters]);
    },
    removeFilter(filter) {
      window.ProcessMaker.EventBus.$emit("removefilter", filter);
    },
    calcInputHeight() {
      if (!this.$refs?.search_input) {
        return;
      }

      this.$refs.search_input.style.height = "auto";
      // Font size * line height in rems (1.5)
      const fontSize = parseFloat(
        getComputedStyle(this.$refs.search_input).fontSize,
      );
      const lineHeight = fontSize * 1.5;

      // Padding top and bottom (0.4rem each)
      const padding = fontSize * 0.4 * 2;
      const currentHeight = padding + this.textAreaLines * lineHeight;

      this.showScrollbars = false;
      this.$nextTick(() => {
        if (!this.$refs?.search_input) {
          return;
        }
        if (currentHeight <= this.$refs.search_input.scrollHeight) {
          this.showScrollbars = true;
          this.$refs.search_input.style.height = `${currentHeight}px`;
          this.$emit("inputresize", currentHeight);
        } else {
          this.$refs.search_input.style.height = `${this.$refs.search_input.scrollHeight}px`;
          this.$emit("inputresize", this.$refs.search_input.scrollHeight);
        }
      });
    },
    onInput() {
      this.$emit("pmqlchange", this.query);
      this.$emit("input", this.query);
    },
    runSearch() {
      this.pmql = "";
      if (this.query === "") {
        this.$emit("submit", "");
        this.$emit("input", "");
        return;
      }

      if (this.query.isPMQL()) {
        this.$emit("submit", this.query);
        this.$emit("input", this.query);
      } else if (this.aiEnabledLocal) {
        this.runNLQToPMQL();
      } else if (!this.query.isPMQL() && !this.aiEnabledLocal) {
        const fullTextSearch = `(fulltext LIKE "%${this.query}%")`;
        this.pmql = fullTextSearch;
        this.$emit("submit", fullTextSearch);
        this.$emit("input", fullTextSearch);
      }
      this.calcInputHeight();
    },
    clearQuery() {
      this.query = "";
      this.runSearch();
    },
    runNLQToPMQL() {
      const params = {
        question: this.query,
        type: this.searchType,
      };

      this.aiLoading = true;

      ProcessMaker.apiClient
        .post("/package-ai/nlqToPmql", params)
        .then((response) => {
          this.pmql = response.data.result;
          this.usage = response.data.usage;
          this.$emit("submit", this.pmql);
          this.$emit("input", this.pmql);
          this.aiLoading = false;
          this.calcInputHeight();
        })
        .catch((error) => {
          window.ProcessMaker.alert(
            this.$t("An error ocurred while calling OpenAI endpoint."),
            "danger",
          );
          const fullTextSearch = `(fulltext LIKE "%${params.question}%")`;
          this.pmql = fullTextSearch;
          this.$emit("submit", fullTextSearch);
          this.$emit("input", fullTextSearch);
          this.aiLoading = false;
          this.calcInputHeight();
        });
    },
    copyToClipboard() {
      const textToCopy = document.getElementById("textToCopy").textContent;
      const textArea = document.createElement("textarea");
      textArea.value = textToCopy;
      document.body.appendChild(textArea);
      textArea.select();
      document.execCommand("copy");
      document.body.removeChild(textArea);

      window.ProcessMaker.alert(
        this.$t("Text copied to the clipboard"),
        "success",
        5,
        true,
      );
    },
    capitalizeString(string) {
      if (string === "") {
        return "";
      }
      let str = string.toLowerCase();
      return str.charAt(0).toUpperCase() + str.slice(1);
    }
  },
};
</script>

<style lang="scss">
.search-bar {
  border: 1px solid rgba(0, 0, 0, 0.125);
  border-radius: 3px;
  background: #ffffff;

  &:hover {
    background-color: #fafbfc;
    border-color: #cdddee;
  }
}

.search-bar.is-invalid {
  border-color: #e50130;
}

.pmql-input {
  background: none;
  color: gray;
  padding: 0.4rem 0.75rem;
  display: block;
  width: 100%;
  border: none;
  padding-left: 0.75rem;
  overflow: hidden;
  resize: none;
  min-height: calc(1.5em + 0.75rem);
}

.pmql-input:focus {
  outline: none;
}

input.pmql-input:focus ~ label,
input.pmql-input:valid ~ label {
  top: 3px;
  font-size: 12px;
  color: #0872c2;
}

.input-right-section {
  color: #0872c2;
  font-family: "Open Sans";
  font-weight: 600;
}

.pmql-icons {
  color: #6c757d;
}

.usage-label {
  background: #1c72c224;
  color: #0872c2;
  right: 29px;
  top: 0;
  margin-right: 0.5rem;
  font-weight: 300;
  margin-bottom: 0;
}

.separator {
  border-right: 1px solid rgb(227, 231, 236);
  height: 1.6rem;
  margin-left: 0.5rem;
  margin-right: 0.5rem;
  right: 0;
  top: 15%;
}
.separator-transparent {
  border-right: 1px solid rgba(227, 231, 236, 0);
  height: 1.6rem;
  margin-left: 0.5rem;
  margin-right: 0.5rem;
  right: 0;
  top: 15%;
}
.separator-horizontal {
  border: 0;
  border-bottom: 1px dashed rgb(227, 231, 236);
  height: 0;
  margin: 5px 7px 0 8px;
}

.badge-success {
  color: #00875a;
  background-color: #00875a26;
}

.filter-dropdown-panel-container {
  min-width: 30rem;
  background: #ffffff;
  border: 1px solid rgba(0, 0, 0, 0.125);
  box-shadow: 0 6px 12px 2px rgba(0, 0, 0, 0.168627451);
  position: absolute;
  left: 0;
  top: 2.5rem;
  border-radius: 3px;
  z-index: 1;
  max-width: 40rem;
}

.selected-filter-item {
  background: #deebff;
  padding: 4px 9px 4px 9px;
  color: #104a75;
  border: 0;
  border-radius: 4px;
  margin-right: 0.5em;
  font-size: 0.8rem;
}

.selected-filter-key {
  text-transform: capitalize;
  font-weight: 700;
}

.filter-counter {
  background: #ebeef2 !important;
  font-weight: 400;
}
.pmql-badge {
  background-color: #f2f8fe;
  color: #6a7888;
  margin-right: 10px;
}
.pmql-tooltip {
  opacity: 1 !important;
}
.pmql-tooltip .tooltip-inner {
  background-color: #f2f8fe;
  color: #6a7888;
  box-shadow: -5px 5px 5px rgba(0, 0, 0, 0.3);
  max-width: none;
  padding: 14px;
  border-radius: 7px;
}
.pmql-tooltip .arrow::before {
  border-bottom-color: #f2f8fe !important;
  border-top-color: #f2f8fe !important;
}
.copy-icon {
  margin-left: 14px;
}
</style>
