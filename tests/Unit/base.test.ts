import { mount } from "@vue/test-utils";
import { describe, expect, test } from "vitest";

describe("base", () => {
    test("mount", () => {
        const wrapper = mount({
            template: "<div>hello</div>",
        });

        expect(wrapper.text()).toBe("hello");
    });
});
