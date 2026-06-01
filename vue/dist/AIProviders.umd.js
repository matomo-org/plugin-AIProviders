(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory(require("CoreHome"), require("vue"), require("CorePluginsAdmin"));
	else if(typeof define === 'function' && define.amd)
		define(["CoreHome", , "CorePluginsAdmin"], factory);
	else if(typeof exports === 'object')
		exports["AIProviders"] = factory(require("CoreHome"), require("vue"), require("CorePluginsAdmin"));
	else
		root["AIProviders"] = factory(root["CoreHome"], root["Vue"], root["CorePluginsAdmin"]);
})((typeof self !== 'undefined' ? self : this), function(__WEBPACK_EXTERNAL_MODULE__19dc__, __WEBPACK_EXTERNAL_MODULE__8bbf__, __WEBPACK_EXTERNAL_MODULE_a5a2__) {
return /******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "plugins/AIProviders/vue/dist/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "fae3");
/******/ })
/************************************************************************/
/******/ ({

/***/ "19dc":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE__19dc__;

/***/ }),

/***/ "8bbf":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE__8bbf__;

/***/ }),

/***/ "8dd7":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "a5a2":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE_a5a2__;

/***/ }),

/***/ "cd4b":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_11_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_11_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_11_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_11_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_1_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_1_1_ProviderCard_vue_vue_type_style_index_0_id_795d013e_lang_less__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("ee6f");
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_11_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_11_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_11_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_11_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_1_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_1_1_ProviderCard_vue_vue_type_style_index_0_id_795d013e_lang_less__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_11_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_11_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_11_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_11_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_1_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_1_1_ProviderCard_vue_vue_type_style_index_0_id_795d013e_lang_less__WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),

/***/ "ee6f":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "f4a9":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_11_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_11_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_11_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_11_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_1_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_1_1_ManageAIProviders_vue_vue_type_style_index_0_id_a772204a_lang_less__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("8dd7");
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_11_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_11_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_11_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_11_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_1_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_1_1_ManageAIProviders_vue_vue_type_style_index_0_id_a772204a_lang_less__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_11_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_11_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_11_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_11_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_1_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_1_1_ManageAIProviders_vue_vue_type_style_index_0_id_a772204a_lang_less__WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),

/***/ "fae3":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, "ManageAIProviders", function() { return /* reexport */ ManageAIProviders; });

// CONCATENATED MODULE: ./node_modules/@vue/cli-service/lib/commands/build/setPublicPath.js
// This file is imported into lib/wc client bundles.

if (typeof window !== 'undefined') {
  var currentScript = window.document.currentScript
  if (false) { var getCurrentScript; }

  var src = currentScript && currentScript.src.match(/(.+\/)[^/]+\.js(\?.*)?$/)
  if (src) {
    __webpack_require__.p = src[1] // eslint-disable-line
  }
}

// Indicate to webpack that this file can be concatenated
/* harmony default export */ var setPublicPath = (null);

// EXTERNAL MODULE: external {"commonjs":"vue","commonjs2":"vue","root":"Vue"}
var external_commonjs_vue_commonjs2_vue_root_Vue_ = __webpack_require__("8bbf");

// EXTERNAL MODULE: external "CoreHome"
var external_CoreHome_ = __webpack_require__("19dc");

// EXTERNAL MODULE: external "CorePluginsAdmin"
var external_CorePluginsAdmin_ = __webpack_require__("a5a2");

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/AIProviders/vue/src/components/ProviderCard.vue?vue&type=script&lang=ts&setup=true


const _hoisted_1 = ["aria-checked", "aria-disabled", "tabindex"];
const _hoisted_2 = {
  key: 0,
  class: "ai-providers-card-default"
};
const _hoisted_3 = {
  class: "ai-providers-card-inner"
};
const _hoisted_4 = {
  class: "ai-providers-card-header"
};
const _hoisted_5 = {
  class: "ai-providers-card-name"
};
const _hoisted_6 = {
  class: "ai-providers-card-description"
};
const _hoisted_7 = {
  class: "ai-providers-card-actions"
};
const _hoisted_8 = ["disabled"];
const _hoisted_9 = ["disabled"];



/* harmony default export */ var ProviderCardvue_type_script_lang_ts_setup_true = (/*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  __name: 'ProviderCard',
  props: {
    provider: null,
    configuration: null,
    selected: {
      type: Boolean
    },
    usableAsDefault: {
      type: Boolean
    },
    canEdit: {
      type: Boolean
    },
    isTesting: {
      type: Boolean
    },
    isDisconnecting: {
      type: Boolean
    }
  },
  emits: ["select", "update:apiKey", "update:endpointUrl", "test", "disconnect"],
  setup(__props, {
    emit
  }) {
    const props = __props;
    /* eslint-disable func-call-spacing, no-spaced-func */
    /* eslint-enable func-call-spacing, no-spaced-func */
    const hasPendingKey = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      var _props$configuration$, _props$configuration;
      return ((_props$configuration$ = (_props$configuration = props.configuration) === null || _props$configuration === void 0 ? void 0 : _props$configuration.apiKey) !== null && _props$configuration$ !== void 0 ? _props$configuration$ : '') !== '';
    });
    function selectProvider() {
      if (props.usableAsDefault) {
        emit('select');
      }
    }
    return (_ctx, _cache) => {
      var _props$configuration2, _props$configuration3;
      return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
        "aria-checked": __props.selected,
        "aria-disabled": !__props.usableAsDefault,
        class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])([{
          'is-selected': __props.selected,
          'is-not-usable': !__props.usableAsDefault
        }, "ai-providers-card"]),
        role: "radio",
        tabindex: __props.usableAsDefault ? 0 : -1,
        onClick: _cache[4] || (_cache[4] = $event => selectProvider()),
        onKeydown: [_cache[5] || (_cache[5] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withKeys"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => selectProvider(), ["prevent"]), ["enter"])), _cache[6] || (_cache[6] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withKeys"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => selectProvider(), ["prevent"]), ["space"]))]
      }, [__props.selected ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_2, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_DefaultBadge')), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_3, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_4, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", _hoisted_5, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(__props.provider.name), 1)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("p", _hoisted_6, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])(__props.provider.description)), 1), __props.canEdit ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], {
        key: 0
      }, [__props.provider.supportsCustomEndpoint ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CorePluginsAdmin_["Field"]), {
        key: 0,
        "model-value": (_props$configuration2 = __props.configuration) === null || _props$configuration2 === void 0 ? void 0 : _props$configuration2.endpointUrl,
        name: `endpointUrl-${__props.provider.id}`,
        title: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_EndpointUrl'),
        placeholder: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_EndpointUrlPlaceholder'),
        autocomplete: "off",
        "full-width": "",
        uicontrol: "text",
        "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => emit('update:endpointUrl', `${$event}`))
      }, null, 8, ["model-value", "name", "title", "placeholder"])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CorePluginsAdmin_["Field"]), {
        "model-value": (_props$configuration3 = __props.configuration) === null || _props$configuration3 === void 0 ? void 0 : _props$configuration3.apiKey,
        name: `apiKey-${__props.provider.id}`,
        placeholder: __props.provider.configuration.hasApiKey ? Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_ApiKeyAlreadyConfiguredPlaceholder') : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_ApiKeyPlaceholder'),
        title: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_ApiKey'),
        autocomplete: "new-password",
        "full-width": "",
        uicontrol: "password",
        "onUpdate:modelValue": _cache[1] || (_cache[1] = $event => emit('update:apiKey', `${$event}`))
      }, null, 8, ["model-value", "name", "placeholder", "title"]), [[Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["AutoClearPassword"])]]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", {
        class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])([{
          'is-connected': __props.provider.configuration.isUsable
        }, "ai-providers-card-status"])
      }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
        "aria-hidden": "true",
        class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(["icon ai-providers-status-icon", __props.provider.configuration.isUsable ? 'icon-ok' : 'icon-minus'])
      }, null, 2), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(" " + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(__props.provider.configuration.isUsable ? Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_StatusConnected') : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_StatusNotConnected')), 1)], 2), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_7, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
        class: "btn btn-small",
        type: "button",
        disabled: __props.isTesting || !Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(hasPendingKey) && !__props.provider.configuration.hasApiKey,
        onClick: _cache[2] || (_cache[2] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => emit('test'), ["prevent", "stop"]))
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(__props.isTesting ? Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_TestingConnection') : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_TestConnection')), 9, _hoisted_8), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
        class: "btn-flat",
        type: "button",
        disabled: __props.isDisconnecting || !__props.provider.configuration.hasApiKey,
        onClick: _cache[3] || (_cache[3] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => emit('disconnect'), ["prevent", "stop"]))
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(__props.isDisconnecting ? Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_Disconnecting') : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_Disconnect')), 9, _hoisted_9)])], 64)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])], 42, _hoisted_1);
    };
  }
}));
// CONCATENATED MODULE: ./plugins/AIProviders/vue/src/components/ProviderCard.vue?vue&type=script&lang=ts&setup=true
 
// EXTERNAL MODULE: ./plugins/AIProviders/vue/src/components/ProviderCard.vue?vue&type=style&index=0&id=795d013e&lang=less
var ProviderCardvue_type_style_index_0_id_795d013e_lang_less = __webpack_require__("cd4b");

// CONCATENATED MODULE: ./plugins/AIProviders/vue/src/components/ProviderCard.vue





/* harmony default export */ var ProviderCard = (ProviderCardvue_type_script_lang_ts_setup_true);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/AIProviders/vue/src/ManageAIProviders.vue?vue&type=script&lang=ts&setup=true


const ManageAIProvidersvue_type_script_lang_ts_setup_true_hoisted_1 = {
  class: "ai-providers-page"
};
const ManageAIProvidersvue_type_script_lang_ts_setup_true_hoisted_2 = {
  class: "ai-providers-page-header"
};
const ManageAIProvidersvue_type_script_lang_ts_setup_true_hoisted_3 = {
  class: "ai-providers-page-title"
};
const ManageAIProvidersvue_type_script_lang_ts_setup_true_hoisted_4 = {
  class: "ai-providers-page-subtitle"
};
const ManageAIProvidersvue_type_script_lang_ts_setup_true_hoisted_5 = {
  key: 0,
  class: "ai-providers-selected-configuration"
};
const ManageAIProvidersvue_type_script_lang_ts_setup_true_hoisted_6 = {
  class: "ai-providers"
};
const ManageAIProvidersvue_type_script_lang_ts_setup_true_hoisted_7 = {
  class: "ai-providers-defaults-title"
};
const ManageAIProvidersvue_type_script_lang_ts_setup_true_hoisted_8 = {
  class: "ai-providers-section"
};
const ManageAIProvidersvue_type_script_lang_ts_setup_true_hoisted_9 = {
  class: "ai-providers-subsection-title"
};
const _hoisted_10 = {
  class: "ai-providers-section-help"
};
const _hoisted_11 = ["aria-label"];
const _hoisted_12 = {
  key: 1,
  class: "ai-providers-section"
};
const _hoisted_13 = {
  class: "ai-providers-subsection-title"
};
const _hoisted_14 = {
  class: "ai-providers-section-help"
};
const _hoisted_15 = ["aria-label"];
const _hoisted_16 = {
  class: "ai-providers-capability-header"
};
const _hoisted_17 = ["value"];
const _hoisted_18 = {
  class: "ai-providers-capability-label"
};
const _hoisted_19 = {
  key: 0,
  class: "ai-providers-capability-description"
};
const _hoisted_20 = {
  key: 2,
  class: "ai-providers-footer"
};
const _hoisted_21 = ["disabled"];




/* harmony default export */ var ManageAIProvidersvue_type_script_lang_ts_setup_true = (/*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  __name: 'ManageAIProviders',
  setup(__props) {
    const settings = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const isLoading = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const isSaving = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const defaultProviderId = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const defaultCapabilityLevel = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const providerConfigurations = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])({});
    const testingProviders = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])({});
    const disconnectingProviders = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])({});
    const providers = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      var _settings$value;
      return ((_settings$value = settings.value) === null || _settings$value === void 0 ? void 0 : _settings$value.providers) || [];
    });
    const hasUsableProvider = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => providers.value.some(provider => provider.configuration.isUsable));
    const canEditCapabilityLevel = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      var _settings$value2;
      return !!((_settings$value2 = settings.value) !== null && _settings$value2 !== void 0 && _settings$value2.canEditCapabilityLevel);
    });
    const canEditProviderConfiguration = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      var _settings$value3;
      return !!((_settings$value3 = settings.value) !== null && _settings$value3 !== void 0 && _settings$value3.canEditProviderConfiguration);
    });
    const selectedProvider = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => providers.value.find(provider => provider.id === defaultProviderId.value));
    const capabilityLevelOptions = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      var _settings$value4;
      const capabilityLevels = ((_settings$value4 = settings.value) === null || _settings$value4 === void 0 ? void 0 : _settings$value4.capabilityLevels) || {};
      return Object.entries(capabilityLevels).map(([id, keys]) => ({
        id,
        label: Object(external_CoreHome_["translate"])(keys.label),
        description: keys.description ? Object(external_CoreHome_["translate"])(keys.description) : ''
      }));
    });
    const selectedCapabilityLevel = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => capabilityLevelOptions.value.find(capability => capability.id === defaultCapabilityLevel.value));
    const selectedConfigurationLabel = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      if (!selectedProvider.value || !selectedCapabilityLevel.value) {
        return '';
      }
      return Object(external_CoreHome_["translate"])('AIProviders_SelectedConfiguration', selectedProvider.value.name, selectedCapabilityLevel.value.label);
    });
    /**
     * Applies the given settings to the component state.
     * @param nextSettings
     */
    function applySettings(nextSettings) {
      settings.value = nextSettings;
      defaultProviderId.value = nextSettings.defaultProviderId;
      defaultCapabilityLevel.value = nextSettings.defaultCapabilityLevel;
      const nextProviderConfigurations = {};
      nextSettings.providers.forEach(provider => {
        nextProviderConfigurations[provider.id] = {
          apiKey: '',
          endpointUrl: provider.configuration.endpointUrl || ''
        };
      });
      providerConfigurations.value = nextProviderConfigurations;
    }
    function markProviderUsable(providerId) {
      if (!settings.value) {
        return;
      }
      const provider = settings.value.providers.find(p => p.id === providerId);
      if (provider) {
        provider.configuration = Object.assign(Object.assign({}, provider.configuration), {}, {
          hasApiKey: true,
          isUsable: true
        });
      }
      if (!defaultProviderId.value) {
        defaultProviderId.value = providerId;
      }
    }
    function getCleanErrorMessage(error) {
      let message = '';
      if (error && typeof error === 'object' && 'message' in error) {
        message = `${error.message}`;
      } else {
        message = `${error}`;
      }
      return message.replace(/\s*#\d+\s+[\s\S]*$/, '').replace(/\s+/g, ' ').trim();
    }
    function showErrorNotification(error, id) {
      const cleaned = getCleanErrorMessage(error);
      const isUseful = cleaned && cleaned !== 'Something went wrong';
      const message = isUseful ? Object(external_CoreHome_["translate"])('AIProviders_RequestFailed', cleaned) : Object(external_CoreHome_["translate"])('AIProviders_UnexpectedError');
      return external_CoreHome_["NotificationsStore"].show({
        message,
        type: 'transient',
        id,
        context: 'error'
      });
    }
    async function loadSettings() {
      isLoading.value = true;
      try {
        const response = await external_CoreHome_["AjaxHelper"].fetch({
          method: 'AIProviders.getSettings'
        }, {
          createErrorNotification: false
        });
        applySettings(response);
      } catch (error) {
        showErrorNotification(error, 'aiProvidersLoadError');
      } finally {
        isLoading.value = false;
      }
    }
    function updateApiKey(providerId, apiKey) {
      providerConfigurations.value[providerId] = Object.assign(Object.assign({}, providerConfigurations.value[providerId]), {}, {
        apiKey
      });
    }
    function updateEndpointUrl(providerId, endpointUrl) {
      providerConfigurations.value[providerId] = Object.assign(Object.assign({}, providerConfigurations.value[providerId]), {}, {
        endpointUrl
      });
    }
    async function disconnectProvider(providerId) {
      disconnectingProviders.value[providerId] = true;
      try {
        const response = await external_CoreHome_["AjaxHelper"].post({
          method: 'AIProviders.disconnectProvider'
        }, {
          providerId
        }, {
          withTokenInUrl: true,
          createErrorNotification: false
        });
        applySettings(response);
        external_CoreHome_["NotificationsStore"].show({
          message: Object(external_CoreHome_["translate"])('AIProviders_DisconnectSuccess'),
          type: 'transient',
          id: `aiProvidersDisconnect-${providerId}`,
          context: 'success'
        });
      } catch (error) {
        showErrorNotification(error, `aiProvidersDisconnectError-${providerId}`);
      } finally {
        disconnectingProviders.value[providerId] = false;
      }
    }
    async function testConnection(providerId) {
      testingProviders.value[providerId] = true;
      try {
        const response = await external_CoreHome_["AjaxHelper"].post({
          method: 'AIProviders.testConnection'
        }, {
          providerId,
          providerConfiguration: JSON.stringify(providerConfigurations.value[providerId] || {})
        }, {
          withTokenInUrl: true,
          createErrorNotification: false
        });
        markProviderUsable(providerId);
        external_CoreHome_["NotificationsStore"].show({
          message: Object(external_CoreHome_["translate"])('AIProviders_TestConnectionSuccess', response.providerName, response.text),
          type: 'transient',
          id: `aiProvidersTest-${providerId}`,
          context: 'success'
        });
      } catch (error) {
        showErrorNotification(error, `aiProvidersTestError-${providerId}`);
      } finally {
        testingProviders.value[providerId] = false;
      }
    }
    function cancelChanges() {
      if (settings.value) {
        applySettings(settings.value);
      }
    }
    /**
     * Saves the current settings to the server.
     */
    async function saveSettings() {
      isSaving.value = true;
      try {
        const response = await external_CoreHome_["AjaxHelper"].post({
          method: 'AIProviders.saveSettings'
        }, {
          defaultProviderId: defaultProviderId.value,
          defaultCapabilityLevel: defaultCapabilityLevel.value,
          providerConfigurations: JSON.stringify(providerConfigurations.value)
        }, {
          withTokenInUrl: true,
          createErrorNotification: false
        });
        applySettings(response);
        const notificationInstanceId = external_CoreHome_["NotificationsStore"].show({
          message: Object(external_CoreHome_["translate"])('AIProviders_SettingsSaveSuccess'),
          type: 'transient',
          id: 'aiProvidersSettings',
          context: 'success'
        });
        external_CoreHome_["NotificationsStore"].scrollToNotification(notificationInstanceId);
      } catch (error) {
        const notificationInstanceId = showErrorNotification(error, 'aiProvidersSettingsError');
        external_CoreHome_["NotificationsStore"].scrollToNotification(notificationInstanceId);
      } finally {
        isSaving.value = false;
      }
    }
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["onMounted"])(loadSettings);
    return (_ctx, _cache) => {
      return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", ManageAIProvidersvue_type_script_lang_ts_setup_true_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("header", ManageAIProvidersvue_type_script_lang_ts_setup_true_hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h2", ManageAIProvidersvue_type_script_lang_ts_setup_true_hoisted_3, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_MenuTitle')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("p", ManageAIProvidersvue_type_script_lang_ts_setup_true_hoisted_4, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_ConfigurationIntro')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(selectedConfigurationLabel) ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("span", ManageAIProvidersvue_type_script_lang_ts_setup_true_hoisted_5, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(selectedConfigurationLabel)), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), isLoading.value ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["ActivityIndicator"]), {
        key: 0,
        loading: isLoading.value
      }, null, 8, ["loading"])) : settings.value ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["ContentBlock"]), {
        key: 1
      }, {
        default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])((Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", ManageAIProvidersvue_type_script_lang_ts_setup_true_hoisted_6, [!Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(canEditProviderConfiguration) ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["Alert"]), {
          key: 0,
          severity: "info"
        }, {
          default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_CloudConfigurationHelp')), 1)]),
          _: 1
        })) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h3", ManageAIProvidersvue_type_script_lang_ts_setup_true_hoisted_7, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_DefaultsTitle')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("section", ManageAIProvidersvue_type_script_lang_ts_setup_true_hoisted_8, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h4", ManageAIProvidersvue_type_script_lang_ts_setup_true_hoisted_9, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_DefaultProvider')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("p", _hoisted_10, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_DefaultProviderHelp')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", {
          "aria-label": Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_DefaultProvider'),
          class: "ai-providers-cards",
          role: "radiogroup"
        }, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(providers), provider => {
          return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(ProviderCard, {
            key: provider.id,
            "can-edit": Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(canEditProviderConfiguration),
            configuration: providerConfigurations.value[provider.id],
            "is-disconnecting": !!disconnectingProviders.value[provider.id],
            "is-testing": !!testingProviders.value[provider.id],
            provider: provider,
            selected: defaultProviderId.value === provider.id,
            "usable-as-default": provider.configuration.isUsable,
            onDisconnect: $event => disconnectProvider(provider.id),
            onSelect: $event => provider.configuration.isUsable ? defaultProviderId.value = provider.id : null,
            onTest: $event => testConnection(provider.id),
            "onUpdate:apiKey": $event => updateApiKey(provider.id, $event),
            "onUpdate:endpointUrl": $event => updateEndpointUrl(provider.id, $event)
          }, null, 8, ["can-edit", "configuration", "is-disconnecting", "is-testing", "provider", "selected", "usable-as-default", "onDisconnect", "onSelect", "onTest", "onUpdate:apiKey", "onUpdate:endpointUrl"]);
        }), 128))], 8, _hoisted_11), !Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(hasUsableProvider) ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["Alert"]), {
          key: 0,
          class: "ai-providers-default-warning",
          severity: "warning"
        }, {
          default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_NoDefaultProviderWarning')), 1)]),
          _: 1
        })) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(canEditCapabilityLevel) ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("section", _hoisted_12, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h4", _hoisted_13, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_DefaultCapabilityLevel')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("p", _hoisted_14, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_DefaultCapabilityLevelHelp')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", {
          "aria-label": Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_DefaultCapabilityLevel'),
          class: "ai-providers-capability-cards",
          role: "radiogroup"
        }, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(capabilityLevelOptions), capability => {
          return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("label", {
            key: capability.id,
            class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])([{
              'is-selected': defaultCapabilityLevel.value === capability.id
            }, "ai-providers-capability-card"])
          }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_16, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
            "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => defaultCapabilityLevel.value = $event),
            value: capability.id,
            name: "defaultCapabilityLevel",
            type: "radio"
          }, null, 8, _hoisted_17), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vModelRadio"], defaultCapabilityLevel.value]]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", _hoisted_18, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(capability.label), 1)]), capability.description ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_19, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(capability.description), 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)], 2);
        }), 128))], 8, _hoisted_15)])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])), [[Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CorePluginsAdmin_["Form"])]])]),
        _: 1
      })) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), settings.value ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_20, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
        disabled: isSaving.value,
        class: "btn btn-outline",
        type: "button",
        onClick: _cache[1] || (_cache[1] = $event => cancelChanges())
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('General_Cancel')), 9, _hoisted_21), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CorePluginsAdmin_["SaveButton"]), {
        saving: isSaving.value,
        onConfirm: _cache[2] || (_cache[2] = $event => saveSettings())
      }, null, 8, ["saving"])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]);
    };
  }
}));
// CONCATENATED MODULE: ./plugins/AIProviders/vue/src/ManageAIProviders.vue?vue&type=script&lang=ts&setup=true
 
// EXTERNAL MODULE: ./plugins/AIProviders/vue/src/ManageAIProviders.vue?vue&type=style&index=0&id=a772204a&lang=less
var ManageAIProvidersvue_type_style_index_0_id_a772204a_lang_less = __webpack_require__("f4a9");

// CONCATENATED MODULE: ./plugins/AIProviders/vue/src/ManageAIProviders.vue





/* harmony default export */ var ManageAIProviders = (ManageAIProvidersvue_type_script_lang_ts_setup_true);
// CONCATENATED MODULE: ./plugins/AIProviders/vue/src/index.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

// CONCATENATED MODULE: ./node_modules/@vue/cli-service/lib/commands/build/entry-lib-no-default.js




/***/ })

/******/ });
});
//# sourceMappingURL=AIProviders.umd.js.map